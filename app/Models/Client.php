<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'email',
        'phone_number',
        'duplicate_group_id',
        'import_batch_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function duplicates()
    {
        return $this->hasMany(Client::class, 'duplicate_group_id', 'id');
    }

    public function groupRoot()
    {
        return $this->belongsTo(Client::class, 'duplicate_group_id');
    }

    public static function canonicalKey(array $row): string
    {
        $company = isset($row['company_name']) ? mb_strtolower(trim($row['company_name'])) : '';
        $email = isset($row['email']) ? mb_strtolower(trim($row['email'])) : '';
        $phone = isset($row['phone_number']) ? preg_replace('/\D+/', '', trim((string)$row['phone_number'])) : '';
        return $company . '|' . $email . '|' . $phone;
    }
}
