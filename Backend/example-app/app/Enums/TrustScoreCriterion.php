<?php

namespace App\Enums;

enum TrustScoreCriterion: string
{
    case TAX_CERTIFICATE = 'tax_certificate';                 // +30 pts (ARCA/AFIP)
    case MUNICIPAL_PERMIT = 'municipal_permit';               // +30 pts (Habilitación / Oblea)
    case MUNICIPAL_TAX_RECEIPT = 'municipal_tax_receipt';     // +30 pts (Boleta ARMSa/Tasa Comercial)
    case PEST_CONTROL_CERTIFICATE = 'pest_control_certificate'; // +20 pts (Desinfección / Fumigación)
    case GOOGLE_PLACES_PROFILE = 'google_places_profile';     // +20 pts (Ficha en Google validada por admin)
    case PHONE_CALL_VERIFICATION = 'phone_call_verification'; // +30 pts (Llamada al teléfono del local)
    case STORE_COUNTER_PHOTO = 'store_counter_photo';         // +10 pts (Foto interior / mostrador)
    case FACADE_PHOTO = 'facade_photo';                       // +10 pts (Foto exterior)
    case OTHER = 'other';                                     // +0 pts

    public function defaultPoints(): int
    {
        return match ($this) {
            self::TAX_CERTIFICATE,
            self::MUNICIPAL_PERMIT,
            self::MUNICIPAL_TAX_RECEIPT,
            self::PHONE_CALL_VERIFICATION => 30,
            self::PEST_CONTROL_CERTIFICATE,
            self::GOOGLE_PLACES_PROFILE => 20,
            self::STORE_COUNTER_PHOTO,
            self::FACADE_PHOTO => 10,
            self::OTHER => 0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::TAX_CERTIFICATE => 'Constancia de CUIT (ARCA/AFIP)',
            self::MUNICIPAL_PERMIT => 'Habilitación Municipal u Oblea',
            self::MUNICIPAL_TAX_RECEIPT => 'Boleta de Tasa Municipal (ARMSa/TISSH)',
            self::PEST_CONTROL_CERTIFICATE => 'Certificado de Desinfección / Fumigación',
            self::GOOGLE_PLACES_PROFILE => 'Ficha comercial verificada en Google Places',
            self::PHONE_CALL_VERIFICATION => 'Llamada telefónica de validación confirmada',
            self::STORE_COUNTER_PHOTO => 'Fotografía del mostrador o cocina interior',
            self::FACADE_PHOTO => 'Fotografía de la fachada exterior',
            self::OTHER => 'Otro documento',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
