<?php
enum Status: string
{
    // Studio & Equipment Statuses
    case Available = 'available';
    case InUse     = 'in_use';
    case Maintenance = 'maintenance';

    // Booking Statuses
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Canceled = 'canceled';
}
