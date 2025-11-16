<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;

/**
 * HasHierarchy Trait
 * 
 * Provides hierarchical functionality for models that have parent-child relationships.
 * This trait includes methods to traverse the hierarchy tree in both directions.
 */
trait HasHierarchy
{
    /**
     * Get the parent foreign key name for this model.
     * Override this method in your model if you use a different naming convention.
     */
    protected function getParentKeyName(): string
    {
        return 'parent_' . strtolower(class_basename(static::class)) . '_id';
    }

    /**
     * Get the root (top-level) model in the hierarchy.
     */
    public function getRoot()
    {
        $current = $this;
        $parentKeyName = $this->getParentKeyName();
        
        while ($current->{$parentKeyName}) {
            $parent = $current->getParent();
            if (!$parent) {
                break;
            }
            $current = $parent;
        }
        
        return $current;
    }

    /**
     * Get the parent model.
     */
    public function getParent()
    {
        $parentKeyName = $this->getParentKeyName();
        $parentId = $this->{$parentKeyName};
        
        if (!$parentId) {
            return null;
        }
        
        return static::find($parentId);
    }

    /**
     * Get all children (direct descendants only).
     */
    public function getChildren(): Collection
    {
        $parentKeyName = $this->getParentKeyName();
        
        return static::where($parentKeyName, $this->id)->get();
    }

    /**
     * Get all descendants (children, grandchildren, etc.).
     */
    public function getDescendants(): Collection
    {
        $descendants = new Collection();
        $children = $this->getChildren();
        
        foreach ($children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }
        
        return $descendants;
    }

    /**
     * Get all ancestors (parent, grandparent, etc.).
     */
    public function getAncestors(): Collection
    {
        $ancestors = new Collection();
        $parent = $this->getParent();
        
        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->getParent();
        }
        
        return $ancestors;
    }

    /**
     * Get the depth level in the hierarchy (root is 0).
     */
    public function getDepth(): int
    {
        return $this->getAncestors()->count();
    }

    /**
     * Check if this model is a root (has no parent).
     */
    public function isRoot(): bool
    {
        $parentKeyName = $this->getParentKeyName();
        return is_null($this->{$parentKeyName});
    }

    /**
     * Check if this model is a leaf (has no children).
     */
    public function isLeaf(): bool
    {
        return $this->getChildren()->isEmpty();
    }

    /**
     * Check if this model is an ancestor of the given model.
     */
    public function isAncestorOf($model): bool
    {
        return $model->getAncestors()->contains('id', $this->id);
    }

    /**
     * Check if this model is a descendant of the given model.
     */
    public function isDescendantOf($model): bool
    {
        return $this->getAncestors()->contains('id', $model->id);
    }

    /**
     * Get all siblings (models with the same parent).
     */
    public function getSiblings(): Collection
    {
        $parentKeyName = $this->getParentKeyName();
        $parentId = $this->{$parentKeyName};
        
        $query = static::where($parentKeyName, $parentId)
                      ->where('id', '!=', $this->id);
        
        return $query->get();
    }

    /**
     * Get the path from root to this model.
     */
    public function getPath(): Collection
    {
        $path = $this->getAncestors()->reverse();
        $path->push($this);
        
        return $path;
    }

    /**
     * Get a hierarchical tree structure starting from this model.
     */
    public function getTree(): array
    {
        return [
            'model' => $this,
            'children' => $this->getChildren()->map(function ($child) {
                return $child->getTree();
            })->toArray()
        ];
    }

    /**
     * Scope to get only root models.
     */
    public function scopeRoots($query)
    {
        $parentKeyName = $this->getParentKeyName();
        return $query->whereNull($parentKeyName);
    }

    /**
     * Scope to get only leaf models.
     */
    public function scopeLeaves($query)
    {
        $parentKeyName = $this->getParentKeyName();
        $tableName = $this->getTable();
        
        return $query->whereNotExists(function ($subQuery) use ($parentKeyName, $tableName) {
            $subQuery->select('id')
                    ->from($tableName . ' as sub')
                    ->whereRaw("sub.{$parentKeyName} = {$tableName}.id");
        });
    }
}