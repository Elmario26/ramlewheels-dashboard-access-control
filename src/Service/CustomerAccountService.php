<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CustomerAccountService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private CustomerRepository $customerRepository,
    ) {}

    public function isCustomerUser(User $user): bool
    {
        return $user->getRole() === 'customer'
            || in_array('ROLE_CUSTOMER', $user->getRoles(), true);
    }

    /**
     * Ensure a CRM Customer record exists for an API / app customer user.
     */
    public function ensureCustomerRecord(User $user): ?Customer
    {
        if (!$this->isCustomerUser($user)) {
            return null;
        }

        if ($user->getEmail()) {
            $existing = $this->customerRepository->findByEmail($user->getEmail());
            if ($existing !== null) {
                return $existing;
            }
        }

        $customer = new Customer();
        $customer->setFirstName($user->getFirstName() ?? 'Unknown');
        $customer->setLastName($this->normalizeLastName($user->getLastName()));
        $customer->setEmail($user->getEmail());
        $customer->setPhone($user->getPhone());
        $customer->setCreatedAt($user->getCreatedAt() ?? new \DateTime());
        $customer->setNotes('Registered via API / mobile app');

        $this->entityManager->persist($customer);

        return $customer;
    }

    /**
     * Backfill Customer rows for all users with ROLE_CUSTOMER or role "customer".
     *
     * @return int Number of new Customer records created
     */
    public function syncAllApiCustomers(): int
    {
        $created = 0;

        foreach ($this->userRepository->findCustomerUsers() as $user) {
            $email = $user->getEmail();
            if (!$email || $this->customerRepository->findByEmail($email) !== null) {
                continue;
            }

            $this->ensureCustomerRecord($user);
            ++$created;
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        return $created;
    }

    private function normalizeLastName(?string $lastName): string
    {
        $lastName = trim((string) $lastName);

        if ($lastName !== '' && strlen($lastName) >= 2) {
            return $lastName;
        }

        return 'Customer';
    }
}
