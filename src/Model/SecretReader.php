<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/** Reads environment secrets or encrypted configuration, rejecting unreadable or corrupt values. */
class SecretReader
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The effective secret: environment variable if set, else the decrypted config value.
     *
     * @param string $envName
     * @param string $configPath
     * @param mixed $store
     * @return string empty when unset, or when the stored value cannot be trusted
     */
    public function read(string $envName, string $configPath, $store = null): string
    {
        $fromEnvironment = $this->fromEnvironment($envName);
        if ($fromEnvironment !== '') {
            return $fromEnvironment;
        }

        $stored = (string) $this->storedValue($configPath, $store);
        if ($stored === '') {
            return '';
        }

        if (!$this->isCiphertext($stored)) {
            $this->logger->error(sprintf(
                'Hyvä AI: the value at "%s" is not Magento ciphertext, so it cannot be '
                . 'decrypted and is being ignored. It was most likely injected as plaintext — a '
                . 'CONFIG__… variable, or a row written directly into core_config_data. Use the '
                . '%s environment variable instead, which is read as-is.',
                $configPath,
                $envName
            ));

            return '';
        }

        $decrypted = (string) $this->encryptor->decrypt($stored);

        // Decryption with the wrong key fails PKCS7 padding validation and yields an empty
        // string, which is otherwise indistinguishable from "not configured" — so an
        // operator would see the feature reported as unconfigured while a value sits
        // plainly in the database. Usually a database restored from another instance.
        if ($decrypted === '') {
            $this->logger->error(sprintf(
                'Hyvä AI: the value at "%s" could not be decrypted, most likely because '
                . 'it was encrypted with a different crypt key — a database copied from another '
                . 'instance does this. Re-save the field, or supply the secret via the %s '
                . 'environment variable.',
                $configPath,
                $envName
            ));

            return '';
        }

        // Padding validation rejects a wrong key only ~255 times in 256; the remaining case
        // decrypts to binary. Refusing it keeps the guarantee absolute: this never returns a
        // value nobody chose, so isConfigured() is never true on garbage.
        if ($this->containsControlCharacters($decrypted)) {
            $this->logger->error(sprintf(
                'Hyvä AI: the value at "%s" decrypted to binary data rather than text, so '
                . 'it is being ignored. The stored value is corrupt or was encrypted with a '
                . 'different crypt key. Re-save the field, or use the %s environment variable.',
                $configPath,
                $envName
            ));

            return '';
        }

        return $decrypted;
    }

    /**
     * Whether this secret is supplied by the environment, which makes any stored value moot.
     *
     * @param string $envName
     * @return bool
     */
    public function isFromEnvironment(string $envName): bool
    {
        return $this->fromEnvironment($envName) !== '';
    }

    /**
     * @param string $envName
     * @return string
     */
    private function fromEnvironment(string $envName): string
    {
        // getenv() rather than $_ENV: the latter is populated only when variables_order
        // includes 'E', which is not the default in many php-fpm builds.
        $value = getenv($envName);

        // Trimmed because a trailing newline is the classic way a secret arrives from a
        // file or a secrets manager, and it would otherwise silently break every signature.
        return is_string($value) ? trim($value) : '';
    }

    /**
     * @param string $configPath
     * @param mixed $store
     * @return mixed
     */
    private function storedValue(string $configPath, $store)
    {
        if ($store !== null) {
            return $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $store);
        }

        return $this->scopeConfig->getValue($configPath);
    }

    /**
     * Magento ciphertext always begins "<keyVersion>:<cipherVersion>:". No secret this
     * module handles has that shape in plaintext, so the check never rejects a real
     * encrypted value.
     *
     * @param string $value
     * @return bool
     */
    private function isCiphertext(string $value): bool
    {
        return preg_match('/^\d+:\d+:/', $value) === 1;
    }

    /**
     * Tests for binary rather than for "printable ASCII": a secret may legitimately contain
     * any text, but never control bytes. 48 bytes of AES garbage avoid all of them only
     * about one time in 75,000, so this is an effective filter with no realistic risk of
     * rejecting a real secret.
     *
     * @param string $value
     * @return bool
     */
    private function containsControlCharacters(string $value): bool
    {
        return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1;
    }
}
