<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Staff Interface
 * 
 * Defines the data structure contract for a Staff entity.
 * Represents an employee or staff member in the organization.
 */
interface StaffInterface
{
    /**
     * Get the staff's unique identifier.
     */
    public function getId(): ?int;

    /**
     * Get the staff's employee number.
     */
    public function getEmployeeNumber(): string;

    /**
     * Get the user ID associated with this staff.
     */
    public function getUserId(): ?int;

    /**
     * Get the staff's first name.
     */
    public function getFirstName(): string;

    /**
     * Get the staff's middle name.
     */
    public function getMiddleName(): ?string;

    /**
     * Get the staff's last name.
     */
    public function getLastName(): string;

    /**
     * Get the staff's email address.
     */
    public function getEmail(): ?string;

    /**
     * Get the staff's phone number.
     */
    public function getPhone(): ?string;

    /**
     * Get the hire date.
     */
    public function getHireDate(): \DateTimeInterface;

    /**
     * Get the resignation date.
     */
    public function getResignationDate(): ?\DateTimeInterface;

    /**
     * Get the position ID.
     */
    public function getPositionId(): ?int;

    /**
     * Get the department ID.
     */
    public function getDepartmentId(): ?int;

    /**
     * Get the reporting manager's staff ID.
     */
    public function getReportsToId(): ?int;

    /**
     * Get the staff status.
     */
    public function getStatus(): string;

    /**
     * Check if the staff is active.
     */
    public function isActive(): bool;

    /**
     * Get the creation timestamp.
     */
    public function getCreatedAt(): ?\DateTimeInterface;

    /**
     * Get the last update timestamp.
     */
    public function getUpdatedAt(): ?\DateTimeInterface;

    /**
     * Get the deletion timestamp (soft delete).
     */
    public function getDeletedAt(): ?\DateTimeInterface;
}
