<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Notification Contract
 * 
 * Abstracts notification functionality to remove direct dependencies
 * on specific notification systems.
 */
interface NotificationContract
{
    /**
     * Notify about a staff transfer.
     */
    public function notifyStaffTransfer(StaffTransferInterface $transfer): void;
    
    /**
     * Notify about a staff resignation.
     */
    public function notifyResignation(StaffInterface $staff): void;
    
    /**
     * Notify about organizational changes.
     */
    public function notifyOrganizationalChange(array $changes): void;
    
    /**
     * Notify about company hierarchy changes.
     */
    public function notifyCompanyHierarchyChange(CompanyInterface $company, array $changes): void;
    
    /**
     * Notify about staff creation.
     */
    public function notifyStaffCreated(StaffInterface $staff): void;
    
    /**
     * Notify about department changes.
     */
    public function notifyDepartmentChange(DepartmentInterface $department, string $action): void;
    
    /**
     * Notify about office changes.
     */
    public function notifyOfficeChange(OfficeInterface $office, string $action): void;
    
    /**
     * Send a custom notification with data.
     */
    public function sendCustomNotification(string $type, array $data): void;
}
