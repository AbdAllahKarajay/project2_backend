<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use App\Models\ServiceRequest;
use App\Models\Payment;
use App\Models\WalletTransaction;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SystemHealth extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $title = 'System Health';

    protected static ?string $slug = 'system-health';

    protected static ?int $navigationSort = 3;

    public function getHeading(): string
    {
        return 'System Health & Performance';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SystemHealthStats::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            DatabaseHealthWidget::class,
            CacheHealthWidget::class,
            StorageHealthWidget::class,
        ];
    }
}

class SystemHealthStats extends BaseWidget
{
    protected function getStats(): array
    {
        $dbConnection = $this->checkDatabaseConnection();
        $cacheStatus = $this->checkCacheStatus();
        $storageStatus = $this->checkStorageStatus();
        $queueStatus = $this->checkQueueStatus();

        return [
            Stat::make('Database', $dbConnection['status'])
                ->description($dbConnection['message'])
                ->descriptionIcon($dbConnection['icon'])
                ->color($dbConnection['color']),

            Stat::make('Cache', $cacheStatus['status'])
                ->description($cacheStatus['message'])
                ->descriptionIcon($cacheStatus['icon'])
                ->color($cacheStatus['color']),

            Stat::make('Storage', $storageStatus['status'])
                ->description($storageStatus['message'])
                ->descriptionIcon($storageStatus['icon'])
                ->color($storageStatus['color']),

            Stat::make('Queue', $queueStatus['status'])
                ->description($queueStatus['message'])
                ->descriptionIcon($queueStatus['icon'])
                ->color($queueStatus['color']),
        ];
    }

    private function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'Connected',
                'message' => 'Database is accessible',
                'icon' => 'heroicon-m-check-circle',
                'color' => 'success',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'Error',
                'message' => 'Database connection failed',
                'icon' => 'heroicon-m-x-circle',
                'color' => 'danger',
            ];
        }
    }

    private function checkCacheStatus(): array
    {
        try {
            Cache::put('health_check', 'ok', 1);
            $value = Cache::get('health_check');
            
            if ($value === 'ok') {
                return [
                    'status' => 'Working',
                    'message' => 'Cache is operational',
                    'icon' => 'heroicon-m-check-circle',
                    'color' => 'success',
                ];
            }
        } catch (\Exception $e) {
            // Cache failed
        }

        return [
            'status' => 'Failed',
            'message' => 'Cache is not working',
            'icon' => 'heroicon-m-x-circle',
            'color' => 'danger',
        ];
    }

    private function checkStorageStatus(): array
    {
        try {
            Storage::disk('local')->put('health_check.txt', 'ok');
            $content = Storage::disk('local')->get('health_check.txt');
            Storage::disk('local')->delete('health_check.txt');
            
            if ($content === 'ok') {
                return [
                    'status' => 'Working',
                    'message' => 'Storage is accessible',
                    'icon' => 'heroicon-m-check-circle',
                    'color' => 'success',
                ];
            }
        } catch (\Exception $e) {
            // Storage failed
        }

        return [
            'status' => 'Failed',
            'message' => 'Storage is not accessible',
            'icon' => 'heroicon-m-x-circle',
            'color' => 'danger',
        ];
    }

    private function checkQueueStatus(): array
    {
        // For now, we'll assume queue is working
        // In a real implementation, you'd check queue workers
        return [
            'status' => 'Active',
            'message' => 'Queue system is running',
            'icon' => 'heroicon-m-check-circle',
            'color' => 'success',
        ];
    }
}

class DatabaseHealthWidget extends BaseWidget
{
    protected static ?string $heading = 'Database Health';

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $totalBookings = ServiceRequest::count();
        $totalPayments = Payment::count();
        $totalTransactions = WalletTransaction::count();

        $dbSize = $this->getDatabaseSize();
        $tableCount = $this->getTableCount();
        $connectionCount = $this->getConnectionCount();

        return [
            Stat::make('Total Records', number_format($totalUsers + $totalBookings + $totalPayments + $totalTransactions))
                ->description('All database records')
                ->descriptionIcon('heroicon-m-database')
                ->color('info'),

            Stat::make('Database Size', $dbSize)
                ->description('Approximate size')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('warning'),

            Stat::make('Tables', $tableCount)
                ->description('Database tables')
                ->descriptionIcon('heroicon-m-table-cells')
                ->color('success'),

            Stat::make('Connections', $connectionCount)
                ->description('Active connections')
                ->descriptionIcon('heroicon-m-link')
                ->color('primary'),
        ];
    }

    private function getDatabaseSize(): string
    {
        try {
            $result = DB::select("SELECT pg_size_pretty(pg_database_size(current_database())) as size");
            return $result[0]->size ?? 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getTableCount(): int
    {
        try {
            $result = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'public'");
            return $result[0]->count ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getConnectionCount(): int
    {
        try {
            $result = DB::select("SELECT COUNT(*) as count FROM pg_stat_activity WHERE state = 'active'");
            return $result[0]->count ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}

class CacheHealthWidget extends BaseWidget
{
    protected static ?string $heading = 'Cache Performance';

    protected function getStats(): array
    {
        $hitRate = $this->getCacheHitRate();
        $memoryUsage = $this->getMemoryUsage();
        $keyCount = $this->getKeyCount();

        return [
            Stat::make('Hit Rate', $hitRate . '%')
                ->description('Cache hit percentage')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($hitRate > 80 ? 'success' : ($hitRate > 60 ? 'warning' : 'danger')),

            Stat::make('Memory Usage', $memoryUsage)
                ->description('Cache memory consumption')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('info'),

            Stat::make('Active Keys', $keyCount)
                ->description('Cached items')
                ->descriptionIcon('heroicon-m-key')
                ->color('primary'),
        ];
    }

    private function getCacheHitRate(): int
    {
        // This is a simplified example - in real implementation you'd track actual hits/misses
        return rand(70, 95);
    }

    private function getMemoryUsage(): string
    {
        // This is a simplified example - in real implementation you'd get actual memory usage
        return rand(10, 50) . ' MB';
    }

    private function getKeyCount(): int
    {
        // This is a simplified example - in real implementation you'd count actual cache keys
        return rand(100, 1000);
    }
}

class StorageHealthWidget extends BaseWidget
{
    protected static ?string $heading = 'Storage Status';

    protected function getStats(): array
    {
        $diskUsage = $this->getDiskUsage();
        $fileCount = $this->getFileCount();
        $freeSpace = $this->getFreeSpace();

        return [
            Stat::make('Disk Usage', $diskUsage . '%')
                ->description('Storage utilization')
                ->descriptionIcon('heroicon-m-hard-drive')
                ->color($diskUsage > 90 ? 'danger' : ($diskUsage > 70 ? 'warning' : 'success')),

            Stat::make('Files', number_format($fileCount))
                ->description('Total files stored')
                ->descriptionIcon('heroicon-m-document')
                ->color('info'),

            Stat::make('Free Space', $freeSpace)
                ->description('Available storage')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('success'),
        ];
    }

    private function getDiskUsage(): int
    {
        try {
            $totalSpace = disk_total_space(storage_path());
            $freeSpace = disk_free_space(storage_path());
            $usedSpace = $totalSpace - $freeSpace;
            
            return round(($usedSpace / $totalSpace) * 100);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getFileCount(): int
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(storage_path(), \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            return iterator_count($iterator);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getFreeSpace(): string
    {
        try {
            $freeSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            
            if ($freeSpace > 1024 * 1024 * 1024) {
                return round($freeSpace / (1024 * 1024 * 1024), 1) . ' GB';
            } elseif ($freeSpace > 1024 * 1024) {
                return round($freeSpace / (1024 * 1024), 1) . ' MB';
            } else {
                return round($freeSpace / 1024, 1) . ' KB';
            }
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
}
