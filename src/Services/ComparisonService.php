<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class ComparisonService
{
    /**
     * Builds the full requirement × company matrix for a licitation:
     * requirements (rows), proposals with their scores (columns), and a lookup
     * of evaluation cells keyed by [proposal_id][requirement_id].
     */
    public static function matrix(PDO $pdo, int $licitationId): array
    {
        $requirements = LicitationService::requirements($pdo, $licitationId);
        $proposals = ProposalService::listForLicitation($pdo, $licitationId);

        $stmt = $pdo->prepare(
            'SELECT e.* FROM evaluations e
             JOIN proposals p ON p.id = e.proposal_id
             WHERE p.licitation_id = :licitation_id'
        );
        $stmt->execute(['licitation_id' => $licitationId]);

        $cells = [];
        foreach ($stmt->fetchAll() as $row) {
            $cells[(int) $row['proposal_id']][(int) $row['licitation_requirement_id']] = $row;
        }

        $scores = [];
        foreach ($proposals as $p) {
            $scores[(int) $p['id']] = ScoreService::find($pdo, (int) $p['id']);
        }

        usort($proposals, static function (array $a, array $b) use ($scores): int {
            $sa = $scores[(int) $a['id']]['adherence_percentage'] ?? -1;
            $sb = $scores[(int) $b['id']]['adherence_percentage'] ?? -1;

            return $sb <=> $sa;
        });

        return [
            'requirements' => $requirements,
            'proposals' => $proposals,
            'cells' => $cells,
            'scores' => $scores,
        ];
    }
}
