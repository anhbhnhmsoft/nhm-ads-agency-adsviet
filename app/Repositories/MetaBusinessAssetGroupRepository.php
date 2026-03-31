<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\MetaBusinessAssetGroup;
use Illuminate\Database\Eloquent\Model;

class MetaBusinessAssetGroupRepository extends BaseRepository
{
    protected function model(): MetaBusinessAssetGroup
    {
        return new MetaBusinessAssetGroup();
    }

    /**
     * Cập nhật hoặc tạo mới một Asset Group dựa trên group_id từ Meta
     */
    public function updateOrCreateByGroupId(string $groupId, array $attributes): MetaBusinessAssetGroup
    {
        $model = $this->model()->updateOrCreate(['group_id' => $groupId], $attributes);
        return $model;
    }
}
