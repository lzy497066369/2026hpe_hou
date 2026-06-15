<?php

namespace App\Enums;

enum UploadUsageType: string
{
    case RegistrationMaterial = 'registration_material';
    case WorkCover = 'work_cover';
    case WorkContent = 'work_content';
}
