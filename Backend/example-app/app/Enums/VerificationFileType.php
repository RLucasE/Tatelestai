<?php

namespace App\Enums;

enum VerificationFileType: string
{
    case PDF = 'pdf';
    case JPG = 'jpg';
    case PNG = 'png';
}