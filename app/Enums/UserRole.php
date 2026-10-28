<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Dosen = 'dosen';
    case Mahasiswa = 'mahasiswa';
}
