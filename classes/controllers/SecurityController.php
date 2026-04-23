<?php
require_once __DIR__ . '/../services/SecurityService.php';
require_once __DIR__ . '/../repositories/SecurityRepository.php';

class SecurityController {
    private SecurityService $securityService;

    public function __construct(PDO $pdo) {
        $this->securityService = new SecurityService(new SecurityRepository($pdo));
    }

    public function lockWithPassword(int $userId, string $password): Security {
        return $this->securityService->lockWithPassword($userId, $password);
    }

    public function unlockWithPassword(int $securityId, string $password): bool {
        return $this->securityService->unlockWithPassword($securityId, $password);
    }

    public function changePassword(int $userId, string $current, string $new): bool {
        return $this->securityService->changePassword($userId, $current, $new);
    }
}
