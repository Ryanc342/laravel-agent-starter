<?php

namespace Tests\Support;

use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use OpenSSLAsymmetricKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use RuntimeException;

/** A software authenticator: uses real keys, CBOR encoding and signatures. */
final class VirtualPasskey
{
    private OpenSSLAsymmetricKey $key;

    private string $id = 'weekly-cleanup-credential';

    public function __construct()
    {
        $this->key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'])
            ?: throw new RuntimeException('Could not create a test authenticator key.');
    }

    /** @return array<string, mixed> */
    public function registration(array $options): array
    {
        $details = openssl_pkey_get_details($this->key);
        $publicKey = MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2))
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7))
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1))
            ->add(NegativeIntegerObject::create(-2), ByteStringObject::create($details['ec']['x']))
            ->add(NegativeIntegerObject::create(-3), ByteStringObject::create($details['ec']['y']));
        // UP + UV + attested credential data, with the credential's ES256 public key.
        $data = hash('sha256', 'localhost', true).chr(0x45).pack('N', 0)
            .str_repeat("\0", 16).pack('n', strlen($this->id)).$this->id.(string) $publicKey;
        $attestation = MapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
            ->add(TextStringObject::create('authData'), ByteStringObject::create($data))
            ->add(TextStringObject::create('attStmt'), MapObject::create());

        return $this->credential([
            'clientDataJSON' => Base64UrlSafe::encodeUnpadded($this->clientData('webauthn.create', $options)),
            'attestationObject' => Base64UrlSafe::encodeUnpadded((string) $attestation),
            'transports' => ['internal'],
        ]);
    }

    /** @return array<string, mixed> */
    public function assertion(array $options, string $userHandle): array
    {
        $clientData = $this->clientData('webauthn.get', $options);
        $data = hash('sha256', 'localhost', true).chr(0x05).pack('N', 1);
        openssl_sign($data.hash('sha256', $clientData, true), $signature, $this->key, OPENSSL_ALGO_SHA256);

        return $this->credential([
            'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientData),
            'authenticatorData' => Base64UrlSafe::encodeUnpadded($data),
            'signature' => Base64UrlSafe::encodeUnpadded($signature),
            'userHandle' => Base64UrlSafe::encodeUnpadded($userHandle),
        ]);
    }

    private function clientData(string $type, array $options): string
    {
        return json_encode(['type' => $type, 'challenge' => $options['challenge'], 'origin' => 'https://localhost'], JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed> */
    private function credential(array $response): array
    {
        return [
            'id' => Base64UrlSafe::encodeUnpadded($this->id),
            'rawId' => Base64UrlSafe::encodeUnpadded($this->id),
            'type' => 'public-key',
            'response' => $response,
        ];
    }
}
