# Paperclip Billing Tracker

Standalone CLI tool that generates time tracking & billing reports from Paperclip agent runs. Aggregates run durations by `billingCode` and `projectId` with per-agent breakdowns.

## Setup

```bash
composer install
cp .env.example .env
# Edit .env with your Paperclip credentials
```

## Usage

```bash
# Table output
php bin/billing-report

# JSON output
php bin/billing-report --json

# Filter by status
php bin/billing-report --status=done

# Pass credentials directly
php bin/billing-report --api-url=http://localhost:3000 --api-key=your-key --company-id=your-id
```

## Output Format (JSON)

```json
{
  "companyId": "company-uuid",
  "generatedAt": "2026-03-29T12:00:00+00:00",
  "grandTotalMinutes": 135.5,
  "grandTotalTasks": 8,
  "lines": [
    {
      "projectId": "project-uuid",
      "projectName": "simpletrics",
      "billingCode": "SIM",
      "totalMinutes": 90.0,
      "taskCount": 3,
      "agentBreakdown": [
        {
          "agentId": "agent-uuid",
          "agentName": "Engineer",
          "totalMinutes": 60.0,
          "taskCount": 2
        }
      ]
    }
  ]
}
```

## Environment Variables

| Variable | Description |
|---|---|
| `PAPERCLIP_API_URL` | Paperclip API base URL |
| `PAPERCLIP_API_KEY` | API authentication key |
| `PAPERCLIP_COMPANY_ID` | Target company ID |
