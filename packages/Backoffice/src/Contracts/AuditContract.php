<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Audit Contract
 * 
 * Abstracts audit logging functionality to remove direct dependencies
 * on specific audit logging systems.
 */
interface AuditContract
{
    /**
     * Log changes to a staff member.
     */
    public function logStaffChange(StaffInterface $staff, array $changes): void;
    
    /**
     * Log a staff transfer.
     */
    public function logTransfer(StaffTransferInterface $transfer): void;
    
    /**
     * Log changes to a company.
     */
    public function logCompanyChange(CompanyInterface $company, array $changes): void;
    
    /**
     * Log department changes.
     */
    public function logDepartmentChange(DepartmentInterface $department, array $changes): void;
    
    /**
     * Log office changes.
     */
    public function logOfficeChange(OfficeInterface $office, array $changes): void;
    
    /**
     * Log organizational hierarchy changes.
     */
    public function logHierarchyChange(object $entity, string $type, array $changes): void;
    
    /**
     * Log custom events with context.
     */
    public function logCustomEvent(string $event, array $context): void;
    
    /**
     * Log mass operations.
     */
    public function logMassOperation(string $operation, int $affectedCount, array $context = []): void;
}
