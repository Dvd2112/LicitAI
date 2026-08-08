<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class UploadService
{
    private const MAX_BYTES = 20 * 1024 * 1024;

    /**
     * Validates and moves an uploaded file into a private storage directory.
     * Never trusts the client-supplied name or MIME type.
     *
     * @return array{original_name:string, stored_name:string, mime_type:string, size_bytes:int, path:string}
     */
    public static function storePdf(array $file, string $destDir): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Falha no envio do arquivo.');
        }

        if (($file['size'] ?? 0) <= 0 || $file['size'] > self::MAX_BYTES) {
            throw new RuntimeException('Arquivo excede o tamanho máximo permitido (20 MB).');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Envio inválido.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mime !== 'application/pdf') {
            throw new RuntimeException('Apenas arquivos PDF são aceitos.');
        }

        $originalName = basename((string) ($file['name'] ?? 'documento.pdf'));
        $storedName = bin2hex(random_bytes(16)) . '.pdf';

        if (!is_dir($destDir) && !mkdir($destDir, 0750, true) && !is_dir($destDir)) {
            throw new RuntimeException('Não foi possível preparar o diretório de armazenamento.');
        }

        $destPath = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException('Não foi possível salvar o arquivo.');
        }

        return [
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $mime,
            'size_bytes' => (int) filesize($destPath),
            'path' => $destPath,
        ];
    }

    /** @return array<int, array> */
    public static function storeManyPdfs(array $filesArray, string $destDir): array
    {
        $results = [];
        $names = (array) ($filesArray['name'] ?? []);

        foreach ($names as $i => $name) {
            if (!is_string($name) || $name === '') {
                continue;
            }

            $single = [
                'name' => $filesArray['name'][$i],
                'type' => $filesArray['type'][$i],
                'tmp_name' => $filesArray['tmp_name'][$i],
                'error' => $filesArray['error'][$i],
                'size' => $filesArray['size'][$i],
            ];

            $results[] = self::storePdf($single, $destDir);
        }

        return $results;
    }
}
