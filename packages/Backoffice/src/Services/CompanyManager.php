<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Services;

use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\CompanyInterface;
use Nexus\Backoffice\Exceptions\CircularReferenceException;

/**
 * Company Manager Service
 * 
 * Framework-agnostic service for managing company operations.
 * Contains core business logic for company management including hierarchy validation.
 */
class CompanyManager
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository
    ) {}

    /**
     * Create a new company with validation.
     *
     * @param array $data Company data
     * @return CompanyInterface The created company
     * @throws CircularReferenceException If parent creates circular reference
     */
    public function createCompany(array $data): CompanyInterface
    {
        // Validate parent relationship if provided
        if (isset($data['parent_company_id'])) {
            $this->validateParentRelationship(null, $data['parent_company_id']);
        }

        return $this->companyRepository->create($data);
    }

    /**
     * Update a company with validation.
     *
     * @param int $id Company ID
     * @param array $data Updated company data
     * @return CompanyInterface The updated company
     * @throws CircularReferenceException If parent change creates circular reference
     */
    public function updateCompany(int $id, array $data): CompanyInterface
    {
        // Validate parent relationship if being changed
        if (isset($data['parent_company_id'])) {
            $this->validateParentRelationship($id, $data['parent_company_id']);
        }

        return $this->companyRepository->update($id, $data);
    }

    /**
     * Delete a company.
     *
     * @param int $id Company ID
     * @return bool True if deleted successfully
     */
    public function deleteCompany(int $id): bool
    {
        return $this->companyRepository->delete($id);
    }

    /**
     * Get company hierarchy (all ancestors).
     *
     * @param CompanyInterface $company The company
     * @return array<CompanyInterface> Array of ancestor companies
     */
    public function getAncestors(CompanyInterface $company): array
    {
        $ancestors = [];
        $currentId = $company->getParentCompanyId();

        while ($currentId !== null) {
            $parent = $this->companyRepository->findById($currentId);
            if ($parent === null) {
                break;
            }

            $ancestors[] = $parent;
            $currentId = $parent->getParentCompanyId();
        }

        return $ancestors;
    }

    /**
     * Get company descendants (all children recursively).
     *
     * @param CompanyInterface $company The company
     * @return array<CompanyInterface> Array of descendant companies
     */
    public function getDescendants(CompanyInterface $company): array
    {
        $descendants = [];
        $this->collectDescendants($company->getId(), $descendants);

        return $descendants;
    }

    /**
     * Check if a company is an ancestor of another.
     *
     * @param int $potentialAncestorId Potential ancestor company ID
     * @param int $companyId Company ID
     * @return bool True if it is an ancestor
     */
    public function isAncestor(int $potentialAncestorId, int $companyId): bool
    {
        $company = $this->companyRepository->findById($companyId);
        if ($company === null) {
            return false;
        }

        $ancestors = $this->getAncestors($company);
        foreach ($ancestors as $ancestor) {
            if ($ancestor->getId() === $potentialAncestorId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate parent relationship to prevent circular references.
     *
     * @param int|null $companyId The company being updated (null for new company)
     * @param int|null $parentId The proposed parent company ID
     * @throws CircularReferenceException If the relationship would create a circle
     */
    private function validateParentRelationship(?int $companyId, ?int $parentId): void
    {
        if ($parentId === null) {
            return; // No parent is always valid
        }

        if ($companyId === $parentId) {
            throw new CircularReferenceException('A company cannot be its own parent');
        }

        // For updates, check if the new parent is a descendant
        if ($companyId !== null && $this->isAncestor($companyId, $parentId)) {
            throw new CircularReferenceException('Cannot set parent to a descendant company (circular reference)');
        }
    }

    /**
     * Recursively collect descendants.
     *
     * @param int|null $companyId Company ID
     * @param array $descendants Array to populate with descendants
     */
    private function collectDescendants(?int $companyId, array &$descendants): void
    {
        if ($companyId === null) {
            return;
        }

        $children = $this->companyRepository->getChildren($companyId);
        foreach ($children as $child) {
            $descendants[] = $child;
            $this->collectDescendants($child->getId(), $descendants);
        }
    }
}
