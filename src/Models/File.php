<?php

declare(strict_types=1);

namespace App\Models;


class File extends BaseModel
{
    protected $table = 'files';

    protected $fillable = [
        'type',
        'title',
        'fname',
        'uploader',
        'uploaddate',
        'date'
    ];




    /**
     * Get file path
     */
    public function getFilePath(): string
    {
        $uploadPath = $_ENV['UPLOAD_PATH'] ?? './storage/uploads';
        return $uploadPath . '/' . $this->fname;
    }

    /**
     * Check if file exists on disk
     */
    public function fileExists(): bool
    {
        return file_exists($this->getFilePath());
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'type' => 'string|max:50',
            'title' => 'required|string|max:255',
            'fname' => 'required|string|max:255',
            'uploader' => 'integer|exists:users,id',
            'uploaddate' => 'integer',
            'date' => 'integer'
        ];
    }
}