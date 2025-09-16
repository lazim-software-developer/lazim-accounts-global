<?php

namespace App\Core\Traits;

use App\Core\Services\ResponseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait DeleteTrait
{
    /**
     * Get all records, including soft-deleted ones
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithTrashedRecords(Builder $query)
    {
        return $query->withTrashed();
    }

    /**
     * Get only soft-deleted records
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOnlyDeleted(Builder $query)
    {
        return $query->onlyTrashed();
    }

    /**
     * Get only active (non-deleted) records
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutDeleted(Builder $query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Restore a soft-deleted record
     *
     * @param Model $model
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreRecord(Model $model)
    {
        if ($model->restore()) {
            return ResponseService::success(null, 'Record restored successfully');
        }
        return ResponseService::error('Failed to restore record', 'Restore Error', 500);
    }

    /**
     * Permanently delete a soft-deleted record
     *
     * @param Model $model
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDeleteRecord(Model $model)
    {
        if ($model->forceDelete()) {
            return ResponseService::success(null, 'Record permanently deleted');
        }
        return ResponseService::error('Failed to delete record permanently', 'Delete Error', 500);
    }
}
