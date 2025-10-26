<?php
namespace App\DTOs;

final class ClientImportRowDTO
{
    public string $company_name;
    public ?string $email;
    public ?string $phone_number;
    public int $rowNumber;

    public function __construct(array $data, int $rowNumber)
    {
        $this->company_name = trim($data['company_name'] ?? '');
        $this->email = isset($data['email']) && $data['email'] !== '' ? trim($data['email']) : null;
        $this->phone_number = isset($data['phone_number']) && $data['phone_number'] !== '' ? trim($data['phone_number']) : null;
        $this->rowNumber = $rowNumber;
    }

    public function toArray(): array
    {
        return [
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
        ];
    }
}
