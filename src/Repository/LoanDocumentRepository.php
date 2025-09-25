<?php

namespace App\Repository;

use App\Entity\LoanDocument;
use App\Entity\LoanApplication;
use App\Entity\Enum\DocumentType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoanDocument>
 */
class LoanDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoanDocument::class);
    }

    public function findByApplication(LoanApplication $application): array
    {
        return $this->createQueryBuilder('ld')
            ->where('ld.loanApplication = :application')
            ->setParameter('application', $application)
            ->orderBy('ld.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByApplicationAndType(LoanApplication $application, DocumentType $documentType): ?LoanDocument
    {
        return $this->createQueryBuilder('ld')
            ->where('ld.loanApplication = :application')
            ->andWhere('ld.documentType = :document_type')
            ->setParameter('application', $application)
            ->setParameter('document_type', $documentType->value)
            ->orderBy('ld.uploadedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPendingVerification(): array
    {
        return $this->createQueryBuilder('ld')
            ->where('ld.isVerified = :verified')
            ->andWhere('ld.isRequired = :required')
            ->setParameter('verified', false)
            ->setParameter('required', true)
            ->orderBy('ld.uploadedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDocumentType(DocumentType $documentType): array
    {
        return $this->createQueryBuilder('ld')
            ->where('ld.documentType = :document_type')
            ->setParameter('document_type', $documentType->value)
            ->orderBy('ld.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getApplicationDocumentStatus(LoanApplication $application): array
    {
        $documents = $this->findByApplication($application);
        
        $required = [];
        $optional = [];
        $uploaded = [];
        $verified = [];

        foreach ($documents as $doc) {
            $uploaded[] = $doc->getDocumentType()->value;
            
            if ($doc->isVerified()) {
                $verified[] = $doc->getDocumentType()->value;
            }

            if ($doc->isRequired()) {
                $required[] = $doc->getDocumentType()->value;
            } else {
                $optional[] = $doc->getDocumentType()->value;
            }
        }

        // Get required document types from the enum
        $requiredDocumentTypes = array_map(fn($type) => $type->value, DocumentType::getRequiredTypes());
        
        $missing = array_diff($requiredDocumentTypes, $uploaded);
        $pendingVerification = array_diff($uploaded, $verified);

        return [
            'uploaded' => $uploaded,
            'verified' => $verified,
            'missing' => $missing,
            'pending_verification' => $pendingVerification,
            'required' => $required,
            'optional' => $optional,
            'completion_rate' => empty($requiredDocumentTypes) ? 100 : (count($uploaded) / count($requiredDocumentTypes)) * 100,
            'verification_rate' => empty($uploaded) ? 0 : (count($verified) / count($uploaded)) * 100,
        ];
    }

    public function getDocumentStatistics(): array
    {
        $qb = $this->createQueryBuilder('ld');
        
        $total = $qb->select('COUNT(ld.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $verified = $qb->select('COUNT(ld.id)')
            ->where('ld.isVerified = :verified')
            ->setParameter('verified', true)
            ->getQuery()
            ->getSingleScalarResult();

        $pending = $qb->select('COUNT(ld.id)')
            ->where('ld.isVerified = :verified')
            ->andWhere('ld.isRequired = :required')
            ->setParameter('verified', false)
            ->setParameter('required', true)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'verified' => $verified,
            'pending_verification' => $pending,
            'verification_rate' => $total > 0 ? ($verified / $total) * 100 : 0,
        ];
    }
}