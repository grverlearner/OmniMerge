<?php

namespace App\Services\Tournaments\CompetitionLab;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use JsonException;

class LabStateTokenService
{
    public function encode(array $state): string
    {
        try {
            return Crypt::encryptString(
                json_encode(
                    $state,
                    JSON_THROW_ON_ERROR
                        |
                        JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                )
            );
        } catch (JsonException) {
            $this->fail(
                'No fue posible codificar el estado temporal del Lab.'
            );
        }
    }

    public function decode(string $token): array
    {
        try {
            $json =
                Crypt::decryptString(
                    $token
                );

            $state =
                json_decode(
                    $json,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
        } catch (
            DecryptException
            |
            JsonException) {
            $this->fail(
                'El estado temporal del Lab es inválido o fue modificado.'
            );
        }

        if (! is_array($state)) {
            $this->fail(
                'El estado temporal no tiene una estructura válida.'
            );
        }

        return $state;
    }

    private function fail(
        string $message
    ): never {
        throw ValidationException::withMessages([
            'state_token' => [
                $message,
            ],
        ]);
    }
}
