<?php
namespace Traits;

trait HasTimestamps {
    protected $createdAt = 'created_at';
    protected $updatedAt = 'updated_at';
    public function touch() { $this->{$this->updatedAt} = date('Y-m-d H:i:s'); }
}