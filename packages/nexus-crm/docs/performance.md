# Performance Tuning Guide

## Database Optimization

### Indexes

Ensure these indexes are created for optimal performance:

```sql
-- Entity queries
CREATE INDEX idx_crm_entities_definition_type ON crm_entities (definition_id, entity_type);
CREATE INDEX idx_crm_entities_status ON crm_entities (status);
CREATE INDEX idx_crm_entities_created_at ON crm_entities (created_at);

-- Assignment queries
CREATE INDEX idx_crm_assignments_entity_user ON crm_assignments (crm_entity_id, user_id);
CREATE INDEX idx_crm_assignments_active ON crm_assignments (is_active);

-- Pipeline queries
CREATE INDEX idx_crm_stages_pipeline_order ON crm_stages (pipeline_id, order);

-- JSON field indexes (if supported)
CREATE INDEX idx_crm_entities_data_gin ON crm_entities USING GIN (data);
```

### Query Optimization

Use eager loading to prevent N+1 queries:

```php
// Bad - N+1 queries
$entities = CrmEntity::all();
foreach ($entities as $entity) {
    echo $entity->definition->name;
}

// Good - Single query
$entities = CrmEntity::with('definition')->get();
```

### Database Configuration

Optimize MySQL/PostgreSQL settings:

```ini
# MySQL
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_size = 256M

# PostgreSQL
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
```

## Caching Strategy

### Dashboard Caching

Dashboard data is cached for 5 minutes. Adjust cache duration:

```php
// In service provider
Cache::remember("crm_dashboard_user_{$userId}", 600, function () use ($userId) {
    // Dashboard logic
});
```

### Pipeline Caching

Cache pipeline configurations:

```php
class PipelineEngine
{
    public function getPipeline($id)
    {
        return Cache::remember("crm_pipeline_{$id}", 3600, function () use ($id) {
            return CrmPipeline::with('stages')->find($id);
        });
    }
}
```

### Entity Schema Caching

Cache CRM definitions:

```php
class CrmDefinition extends Model
{
    public static function getCached($id)
    {
        return Cache::remember("crm_definition_{$id}", 3600, function () use ($id) {
            return static::find($id);
        });
    }
}
```

## Queue Optimization

### Heavy Operations

Move heavy operations to queues:

```php
// In TransitionEntity action
dispatch(new ProcessTransition($entity, $targetStage));

// In job
class ProcessTransition implements ShouldQueue
{
    public function handle()
    {
        // Heavy transition logic
    }
}
```

### Integration Queues

Queue external integrations:

```php
class EmailIntegration implements IntegrationContract
{
    public function execute($entity, array $config, array $context = []): void
    {
        dispatch(new SendEmail($entity, $config));
    }
}
```

## Memory Optimization

### Chunk Processing

Process large datasets in chunks:

```php
CrmEntity::chunk(100, function ($entities) {
    foreach ($entities as $entity) {
        // Process entity
    }
});
```

### Lazy Loading

Use lazy collections for memory efficiency:

```php
$entities = CrmEntity::cursor(); // Lazy collection
foreach ($entities as $entity) {
    // Process one at a time
}
```

## Monitoring and Profiling

### Laravel Debugbar

Install and use Laravel Debugbar for query monitoring:

```bash
composer require barryvdh/laravel-debugbar --dev
```

### Query Logging

Enable query logging in development:

```php
// In AppServiceProvider
if (app()->environment('local')) {
    DB::listen(function ($query) {
        Log::info($query->sql, $query->bindings);
    });
}
```

### Performance Metrics

Track key metrics:

```php
class PerformanceMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $start;

        Log::info('Request duration', [
            'url' => $request->url(),
            'duration' => $duration,
        ]);

        return $response;
    }
}
```

## Scaling Considerations

### Read Replicas

Configure read replicas for dashboard queries:

```php
// In config/database.php
'connections' => [
    'mysql' => [
        'read' => [
            'host' => env('DB_READ_HOST'),
        ],
        'write' => [
            'host' => env('DB_WRITE_HOST'),
        ],
    ],
],
```

Use read connection for queries:

```php
class CrmDashboard
{
    public function forUser(string $userId): array
    {
        return CrmEntity::on('mysql::read')->where(/* ... */)->get();
    }
}
```

### Horizontal Scaling

Use Redis for session and cache storage:

```php
// In config/cache.php
'default' => env('CACHE_DRIVER', 'redis'),

// In config/session.php
'driver' => env('SESSION_DRIVER', 'redis'),
```

### CDN for Assets

Serve static assets from CDN:

```php
// In webpack.mix.js
mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')
   .version(); // Cache busting
```

## Troubleshooting Performance Issues

### Slow Queries

Identify slow queries:

```sql
SELECT sql_text, exec_count, avg_timer_wait
FROM performance_schema.events_statements_summary_by_digest
ORDER BY avg_timer_wait DESC;
```

### Memory Leaks

Monitor memory usage:

```php
$memory = memory_get_peak_usage(true);
Log::info('Memory usage', ['peak' => $memory]);
```

### Cache Invalidation

Clear caches when needed:

```php
// Clear all CRM caches
Cache::tags(['crm'])->flush();

// Or specific caches
Cache::forget("crm_dashboard_user_{$userId}");
```

## Benchmarking

### Load Testing

Use tools like Apache Bench or Siege:

```bash
ab -n 1000 -c 10 http://your-app.com/api/crm/dashboard
```

### Profiling

Use Blackfire or Xdebug for detailed profiling:

```php
// Install Blackfire
curl https://packages.blackfire.io/gpg.key | sudo apt-key add -
echo "deb http://packages.blackfire.io/debian any main" | sudo tee /etc/apt/sources.list.d/blackfire.list
sudo apt update && sudo apt install blackfire-agent blackfire-php
```

## Configuration Tuning

### PHP Configuration

Optimize PHP settings:

```ini
memory_limit = 256M
max_execution_time = 30
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 7963
```

### Laravel Configuration

Tune Laravel settings:

```php
// In config/app.php
'key' => env('APP_KEY'),
'cipher' => 'AES-256-GCB', // Faster cipher

// In config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
    ],
],
```

## Monitoring Tools

### Application Monitoring

- **Laravel Telescope**: Debug and monitor requests
- **Sentry**: Error tracking and performance monitoring
- **New Relic**: Application performance monitoring

### Infrastructure Monitoring

- **Prometheus + Grafana**: Metrics collection and visualization
- **DataDog**: Comprehensive monitoring solution
- **AWS CloudWatch**: Cloud infrastructure monitoring

## Maintenance Tasks

### Regular Cleanup

Set up scheduled cleanup jobs:

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('crm:cleanup-old-data')->weekly();
    $schedule->command('cache:clear')->daily();
}
```

### Database Maintenance

Regular database maintenance:

```sql
-- Analyze tables
ANALYZE TABLE crm_entities;

-- Optimize tables
OPTIMIZE TABLE crm_entities;

-- Clean up old data
DELETE FROM crm_entities WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```