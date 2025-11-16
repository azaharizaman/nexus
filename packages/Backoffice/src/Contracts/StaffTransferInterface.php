<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Staff Transfer Interface
 * 
 * Defines the data structure contract for a Staff Transfer entity.
 * Represents a staff member's transfer from one department/position to another.
 */
interface StaffTransferInterface
{
    /**
     * Get the staff transfer's unique identifier.
     */
    public function getId(): ?int;

    /**
     * Get the staff ID being transferred.
     */
    public function getStaffId(): int;

    /**
     * Get the source department ID (from).
     */
    public function getFromDepartmentId(): ?int;

    /**
     * Get the target department ID (to).
     */
    public function getToDepartmentId(): ?int;

    /**
     * Get the source position ID (from).
     */
    public function getFromPositionId(): ?int;

    /**
     * Get the target position ID (to).
     */
    public function getToPositionId(): ?int;

    /**
     * Get the source unit ID (from).
     */
    public function getFromUnitId(): ?int;

    /**
     * Get the target unit ID (to).
     */
    public function getToUnitId(): ?int;

    /**
     * Get the effective date of the transfer.
     */
    public function getEffectiveDate(): \DateTimeInterface;

    /**
     * Get the transfer reason.
     */
    public function getReason(): ?string;

    /**
     * Get the transfer status.
     */
    public function getStatus(): string;

    /**
     * Get the ID of the user who approved the transfer.
     */
    public function getApprovedBy(): ?int;

    /**
     * Get the approval timestamp.
     */
    public function getApprovedAt(): ?\DateTimeInterface;

    /**
     * Get the creation timestamp.
     */
    public function getCreatedAt(): ?\DateTimeInterface;

    /**
     * Get the last update timestamp.
     */
    public function getUpdatedAt(): ?\DateTimeInterface;
}
