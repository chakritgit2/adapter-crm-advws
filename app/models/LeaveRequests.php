<?php

declare(strict_types=1);

use Phalcon\Db\Enum;
use Phalcon\Mvc\Model;

class LeaveRequests extends Model
{
    use TranslateUuidTrait;

    public const TABLE_NAME = 'leave_requests';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public ?int $id = null;
    public ?string $public_id = null;
    public int $company_id;
    public int $employee_id;
    public int $leave_type_id;
    public string $start_date;
    public string $end_date;
    public int $requested_minutes = 0;
    public string $status = self::STATUS_PENDING;
    public ?string $reason = null;
    public ?string $attachment_url = null;
    public ?int $approved_by = null;
    public string $approved_by_type = 'admin';
    public ?string $created_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo(
            'company_id',
            Companies::class,
            'id',
            ['alias' => 'Company']
        );

        $this->belongsTo(
            'employee_id',
            Employees::class,
            'id',
            ['alias' => 'Employee']
        );

        $this->belongsTo(
            'leave_type_id',
            LeaveTypes::class,
            'id',
            ['alias' => 'LeaveType']
        );

        $this->belongsTo(
            'approved_by',
            AdminUsers::class,
            'id',
            ['alias' => 'Approver']
        );
    }

    /**
     * Count the number of chargeable working days in an inclusive date
     * range, excluding Saturdays, Sundays, and Company Holidays for the
     * given company.
     *
     * A day that is both a weekend and a Company Holiday is only excluded
     * once (no double counting).
     *
     * @param mixed  $db        A Phalcon DB adapter instance.
     * @param int    $companyId Company the leave belongs to.
     * @param string $startDate Inclusive start date (Y-m-d).
     * @param string $endDate   Inclusive end date (Y-m-d).
     * @return int Number of chargeable days (>= 0).
     */
    public static function countChargeableDays($db, int $companyId, string $startDate, string $endDate): int
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);

        if ($start === false || $end === false || $end < $start) {
            return 0;
        }

        // Load Company Holidays overlapping the range into a Y-m-d => true set.
        $rows = $db->fetchAll(
            "SELECT holiday_date FROM company_holidays "
            . "WHERE company_id = :cid AND holiday_date BETWEEN :start AND :end",
            Enum::FETCH_ASSOC,
            ['cid' => $companyId, 'start' => $startDate, 'end' => $endDate]
        );

        $holidays = [];
        foreach ($rows as $row) {
            $holidays[$row['holiday_date']] = true;
        }

        $chargeable = 0;
        $current = $start;
        while ($current <= $end) {
            $dow = (int)date('N', $current); // 1=Mon .. 6=Sat, 7=Sun
            $dateStr = date('Y-m-d', $current);
            $isWeekend = ($dow === 6 || $dow === 7);
            $isHoliday = isset($holidays[$dateStr]);

            if (!$isWeekend && !$isHoliday) {
                $chargeable++;
            }

            $current = strtotime('+1 day', $current);
        }

        return $chargeable;
    }
}
