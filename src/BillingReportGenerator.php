<?php

declare(strict_types=1);

namespace PaperclipBilling;

use DateTimeImmutable;

class BillingReportGenerator
{
    public function __construct(
        private readonly PaperclipClient $client,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generate(?string $status = null): array
    {
        $issues = $this->client->fetchIssues($status);
        $agents = $this->client->fetchAgents();

        $agentMap = [];
        foreach ($agents as $agent) {
            $agentMap[$agent['id']] = $agent['name'] ?? 'Unknown';
        }

        // Filter to issues with startedAt
        $trackable = array_filter($issues, fn(array $issue): bool => $issue['startedAt'] !== null);

        // Group by projectId|billingCode
        $grouped = [];
        foreach ($trackable as $issue) {
            $key = ($issue['projectId'] ?? 'none') . '|' . ($issue['billingCode'] ?? 'none');
            $grouped[$key][] = $issue;
        }

        $lines = [];
        foreach ($grouped as $key => $group) {
            [$projectId, $billingCode] = explode('|', $key);
            $projectId = $projectId === 'none' ? null : $projectId;
            $billingCode = $billingCode === 'none' ? null : $billingCode;

            $projectName = null;
            if ($projectId !== null && isset($group[0]['project']['name'])) {
                $projectName = $group[0]['project']['name'];
            }

            // Agent breakdown
            $agentGroups = [];
            foreach ($group as $issue) {
                $agentId = $issue['assigneeAgentId'] ?? null;
                if ($agentId !== null) {
                    $agentGroups[$agentId][] = $issue;
                }
            }

            $agentBreakdown = [];
            foreach ($agentGroups as $agentId => $tasks) {
                $totalMinutes = array_sum(array_map(fn(array $i): float => $this->calculateMinutes($i), $tasks));
                $agentBreakdown[] = [
                    'agentId' => $agentId,
                    'agentName' => $agentMap[$agentId] ?? 'Unknown',
                    'totalMinutes' => round($totalMinutes, 2),
                    'taskCount' => count($tasks),
                ];
            }

            $totalMinutes = array_sum(array_map(fn(array $i): float => $this->calculateMinutes($i), $group));

            $lines[] = [
                'projectId' => $projectId,
                'projectName' => $projectName,
                'billingCode' => $billingCode,
                'totalMinutes' => round($totalMinutes, 2),
                'taskCount' => count($group),
                'agentBreakdown' => $agentBreakdown,
            ];
        }

        $grandTotalMinutes = array_sum(array_column($lines, 'totalMinutes'));
        $grandTotalTasks = array_sum(array_column($lines, 'taskCount'));

        return [
            'companyId' => $this->client->getCompanyId(),
            'generatedAt' => (new DateTimeImmutable())->format('c'),
            'grandTotalMinutes' => round($grandTotalMinutes, 2),
            'grandTotalTasks' => $grandTotalTasks,
            'lines' => $lines,
        ];
    }

    private function calculateMinutes(array $issue): float
    {
        $start = $issue['startedAt'] ?? null;
        $end = $issue['completedAt'] ?? $issue['cancelledAt'] ?? null;

        if ($start === null) {
            return 0.0;
        }

        $startTime = new DateTimeImmutable($start);
        $endTime = $end !== null ? new DateTimeImmutable($end) : new DateTimeImmutable();

        $diffSeconds = $endTime->getTimestamp() - $startTime->getTimestamp();

        return max(0, $diffSeconds / 60);
    }
}
