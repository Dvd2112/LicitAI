<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Computes proposal adherence scores deterministically from structured evaluation
 * classifications. The AI never assigns a score directly — it only classifies each
 * requirement with evidence; this service is the single place where classifications
 * become numbers.
 */
final class ScoreService
{
    private const POINTS = [
        'atende' => 1.0,
        'atende_parcialmente' => 0.5,
        'nao_atende' => 0.0,
        'evidencia_insuficiente' => 0.0,
        'nao_identificado' => 0.0,
        'requer_revisao' => 0.0,
    ];

    public static function recalculate(PDO $pdo, int $proposalId): array
    {
        $stmt = $pdo->prepare(
            'SELECT e.classification, e.human_classification, e.human_reviewed, r.weight
             FROM evaluations e
             JOIN licitation_requirements r ON r.id = e.licitation_requirement_id
             WHERE e.proposal_id = :proposal_id'
        );
        $stmt->execute(['proposal_id' => $proposalId]);
        $rows = $stmt->fetchAll();

        $counts = ['attended' => 0, 'partially_attended' => 0, 'not_attended' => 0, 'insufficient_evidence' => 0];
        $weightSum = 0.0;
        $pointSum = 0.0;

        foreach ($rows as $row) {
            $effective = $row['human_reviewed'] && $row['human_classification']
                ? $row['human_classification']
                : $row['classification'];

            $weight = (float) $row['weight'];
            $weightSum += $weight;
            $pointSum += $weight * (self::POINTS[$effective] ?? 0.0);

            match ($effective) {
                'atende' => $counts['attended']++,
                'atende_parcialmente' => $counts['partially_attended']++,
                'nao_atende' => $counts['not_attended']++,
                'evidencia_insuficiente', 'nao_identificado' => $counts['insufficient_evidence']++,
                default => null,
            };
        }

        $adherence = $weightSum > 0 ? round(($pointSum / $weightSum) * 100, 2) : 0.0;
        $total = count($rows);

        $stmt = $pdo->prepare(
            'INSERT INTO proposal_scores (
                proposal_id, total_requirements, attended, partially_attended,
                not_attended, insufficient_evidence, adherence_percentage, calculated_at
            ) VALUES (
                :proposal_id, :total, :attended, :partial, :not_attended, :insufficient, :adherence, NOW()
            ) ON DUPLICATE KEY UPDATE
                total_requirements = VALUES(total_requirements),
                attended = VALUES(attended),
                partially_attended = VALUES(partially_attended),
                not_attended = VALUES(not_attended),
                insufficient_evidence = VALUES(insufficient_evidence),
                adherence_percentage = VALUES(adherence_percentage),
                calculated_at = NOW()'
        );
        $stmt->execute([
            'proposal_id' => $proposalId,
            'total' => $total,
            'attended' => $counts['attended'],
            'partial' => $counts['partially_attended'],
            'not_attended' => $counts['not_attended'],
            'insufficient' => $counts['insufficient_evidence'],
            'adherence' => $adherence,
        ]);

        return array_merge($counts, ['total_requirements' => $total, 'adherence_percentage' => $adherence]);
    }

    public static function find(PDO $pdo, int $proposalId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM proposal_scores WHERE proposal_id = :id');
        $stmt->execute(['id' => $proposalId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
