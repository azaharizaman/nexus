# Migration Guides

## Migrating from Salesforce

### Data Mapping

| Salesforce | Nexus CRM |
|------------|-----------|
| Lead | CrmEntity (type: 'lead') |
| Contact | CrmEntity (type: 'contact') |
| Opportunity | CrmEntity (type: 'opportunity') |
| Account | CrmEntity (type: 'account') |

### Export from Salesforce

1. Use Data Export wizard in Salesforce
2. Export Leads, Contacts, Opportunities, Accounts
3. Download CSV files

### Import to Nexus CRM

```php
<?php

namespace App\Console\Commands;

use Nexus\Crm\Models\CrmDefinition;
use Nexus\Crm\Actions\CreateEntity;
use Illuminate\Console\Command;
use League\Csv\Reader;

class ImportSalesforceLeads extends Command
{
    protected $signature = 'crm:import:salesforce-leads {file}';

    public function handle()
    {
        $definition = CrmDefinition::where('type', 'lead')->first();

        $csv = Reader::createFromPath($this->argument('file'));
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $record) {
            app(CreateEntity::class)->execute([
                'definition_id' => $definition->id,
                'data' => [
                    'first_name' => $record['FirstName'],
                    'last_name' => $record['LastName'],
                    'email' => $record['Email'],
                    'company' => $record['Company'],
                    'phone' => $record['Phone'],
                    'status' => $this->mapStatus($record['Status']),
                ],
            ]);
        }

        $this->info('Import completed');
    }

    private function mapStatus($sfStatus)
    {
        $mapping = [
            'Open - Not Contacted' => 'new',
            'Working - Contacted' => 'contacted',
            'Closed - Converted' => 'qualified',
            'Closed - Not Converted' => 'lost',
        ];

        return $mapping[$sfStatus] ?? 'new';
    }
}
```

### Pipeline Migration

Map Salesforce stages to Nexus CRM stages:

```php
$pipeline = CrmPipeline::create([
    'name' => 'Sales Pipeline',
    'definition_id' => $leadDefinition->id,
]);

$stages = [
    ['name' => 'Prospect', 'order' => 1],
    ['name' => 'Contacted', 'order' => 2],
    ['name' => 'Qualified', 'order' => 3],
    ['name' => 'Proposal', 'order' => 4],
    ['name' => 'Negotiation', 'order' => 5],
    ['name' => 'Closed Won', 'order' => 6],
    ['name' => 'Closed Lost', 'order' => 7],
];

foreach ($stages as $stageData) {
    CrmStage::create([
        'pipeline_id' => $pipeline->id,
        'name' => $stageData['name'],
        'order' => $stageData['order'],
    ]);
}
```

## Migrating from HubSpot

### API Export

Use HubSpot API to export contacts and deals:

```php
$hubspot = new HubSpot\Client(['access_token' => env('HUBSPOT_ACCESS_TOKEN')]);

$contacts = $hubspot->contacts()->all();
$deals = $hubspot->deals()->all();
```

### Data Transformation

```php
foreach ($contacts as $contact) {
    app(CreateEntity::class)->execute([
        'definition_id' => $contactDefinition->id,
        'data' => [
            'first_name' => $contact->properties['firstname'],
            'last_name' => $contact->properties['lastname'],
            'email' => $contact->properties['email'],
            'company' => $contact->properties['company'],
        ],
    ]);
}
```

## Migrating from Pipedrive

### CSV Export

1. Export persons, organizations, deals from Pipedrive
2. Map fields to Nexus CRM schema

### Field Mapping

```php
$pipedriveMapping = [
    'name' => 'first_name',
    'org_name' => 'company',
    'email' => 'email',
    'phone' => 'phone',
    'value' => 'budget',
];
```

## Migrating from Zoho CRM

### API Migration

```php
$zoho = new ZohoCRM\Client([
    'client_id' => env('ZOHO_CLIENT_ID'),
    'client_secret' => env('ZOHO_CLIENT_SECRET'),
]);

$leads = $zoho->getRecords('Leads');

foreach ($leads as $lead) {
    app(CreateEntity::class)->execute([
        'definition_id' => $leadDefinition->id,
        'data' => [
            'first_name' => $lead['First_Name'],
            'last_name' => $lead['Last_Name'],
            'email' => $lead['Email'],
            'company' => $lead['Company'],
        ],
    ]);
}
```

## General Migration Steps

1. **Analyze Source Data**: Understand field types and relationships
2. **Create CRM Definitions**: Set up schemas in Nexus CRM
3. **Map Fields**: Create field mapping configuration
4. **Transform Data**: Handle data type conversions and validations
5. **Import in Batches**: Process data in chunks to avoid timeouts
6. **Verify Data**: Spot check imported records
7. **Set Up Pipelines**: Configure stages and transitions
8. **Migrate Users**: Import team members and assign roles
9. **Test Workflows**: Ensure automation works correctly

## Data Validation

Always validate data during migration:

```php
$validator = Validator::make($data, [
    'email' => 'required|email',
    'phone' => 'nullable|string',
    'budget' => 'nullable|numeric|min:0',
]);

if ($validator->fails()) {
    Log::warning('Invalid data during migration', [
        'data' => $data,
        'errors' => $validator->errors(),
    ]);
    continue;
}
```

## Performance Considerations

- Use queue jobs for large imports
- Disable model events during bulk imports
- Use database transactions for data integrity
- Monitor memory usage for large datasets

## Post-Migration Tasks

1. Update integrations and API keys
2. Reconfigure email templates
3. Set up new reporting dashboards
4. Train team on new system
5. Archive old system data