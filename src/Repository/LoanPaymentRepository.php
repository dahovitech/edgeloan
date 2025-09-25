<?php

namespace App\Repository;

use App\Entity\LoanPayment;
use App\Entity\LoanApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoanPayment>
 */
class LoanPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoanPayment::class);
    }

    public function findByApplication(LoanApplication $application): array
    {
        return $this->createQueryBuilder('lp')
            ->where('lp.loanApplication = :application')
            ->setParameter('application', $application)
            ->orderBy('lp.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverduePayments(): array
    {
        return $this->createQueryBuilder('lp')
            ->where('lp.status != :paid_status')
            ->andWhere('lp.dueDate < :today')
            ->setParameter('paid_status', 'paid')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('lp.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingPayments(int $days = 7): array
    {
        $startDate = new \DateTime('today');
        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P' . $days . 'D'));

        return $this->createQueryBuilder('lp')
            ->where('lp.status = :status')
            ->andWhere('lp.dueDate BETWEEN :start_date AND :end_date')
            ->setParameter('status', 'pending')
            ->setParameter('start_date', $startDate)
            ->setParameter('end_date', $endDate)
            ->orderBy('lp.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingPayments(): array
    {
        return $this->createQueryBuilder('lp')
            ->where('lp.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('lp.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('lp')
            ->where('lp.status = :status')
            ->setParameter('status', $status)
            ->orderBy('lp.dueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getPaymentStatistics(LoanApplication $application = null): array
    {
        $qb = $this->createQueryBuilder('lp');
        
        if ($application) {
            $qb->where('lp.loanApplication = :application')
               ->setParameter('application', $application);
        }

        $total = $qb->select('COUNT(lp.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $paid = $qb->select('COUNT(lp.id)')
            ->andWhere('lp.status = :status')
            ->setParameter('status', 'paid')
            ->getQuery()
            ->getSingleScalarResult();

        $overdue = $qb->select('COUNT(lp.id)')
            ->andWhere('lp.status != :paid_status')
            ->andWhere('lp.dueDate < :today')
            ->setParameter('paid_status', 'paid')
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();

        $totalAmount = $qb->select('SUM(lp.amount)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $paidAmount = $qb->select('SUM(lp.paidAmount)')
            ->andWhere('lp.status = :status')
            ->setParameter('status', 'paid')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return [
            'total_payments' => $total,
            'paid_payments' => $paid,
            'overdue_payments' => $overdue,
            'pending_payments' => $total - $paid,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $totalAmount - $paidAmount,
            'payment_rate' => $total > 0 ? ($paid / $total) * 100 : 0,
            'amount_rate' => $totalAmount > 0 ? ($paidAmount / $totalAmount) * 100 : 0,
        ];
    }

    public function getMonthlyPaymentSummary(): array
    {
        $qb = $this->createQueryBuilder('lp');
        
        return $qb->select('YEAR(lp.dueDate) as year, MONTH(lp.dueDate) as month, SUM(lp.amount) as total_due, SUM(lp.paidAmount) as total_paid')
            ->where('lp.dueDate >= :start_date')
            ->groupBy('year, month')
            ->orderBy('year, month')
            ->setParameter('start_date', new \DateTime('-12 months'))
            ->getQuery()
            ->getResult();
    }

    public function createPaymentSchedule(LoanApplication $application): array
    {
        $monthlyPaymentFloat = $application->getMonthlyPaymentFloat();
        $duration = $application->getRequestedDuration();
        
        if (!$monthlyPaymentFloat || !$duration) {
            return [];
        }

        $payments = [];
        $startDate = new \DateTime('first day of next month');

        for ($i = 0; $i < $duration; $i++) {
            $dueDate = clone $startDate;
            $dueDate->add(new \DateInterval('P' . $i . 'M'));

            $payment = new LoanPayment();
            $payment->setLoanApplication($application)
                   ->setAmount($monthlyPaymentFloat)
                   ->setDueDate($dueDate);

            $payments[] = $payment;
        }

        return $payments;
    }
}