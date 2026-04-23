<?php
require_once __DIR__ . '/../repositories/ISecurityRepository.php';
require_once __DIR__ . '/../repositories/SecurityRepository.php';
require_once __DIR__ . '/../entities/Security.php';

class SecurityService {
    private ISecurityRepository $securityRepo;

    public function __construct(ISecurityRepository $securityRepo) {
        $this->securityRepo = $securityRepo;
    }

    public function lockWithPassword(int $userId, string $password): Security {
        if (strlen($password) < 8) throw new InvalidArgumentException('Heslo musí mať aspoň 8 znakov.');
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $security = new Security($userId, 'password', $hash);
        return $this->securityRepo->save($security);
    }

    public function unlockWithPassword(int $securityId, string $password): bool {
        $security = $this->securityRepo->findById($securityId);
        if (!$security) return false;
        return password_verify($password, $security->passwordHash);
    }

    public function isLocked(int $securityId): bool {
        return $this->securityRepo->findById($securityId) !== null;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool {
        $security = $this->securityRepo->findByUser($userId);
        if (!$security || !password_verify($currentPassword, $security->passwordHash)) {
            throw new RuntimeException('Aktuálne heslo je nesprávne.');
        }
        if (strlen($newPassword) < 8) throw new InvalidArgumentException('Nové heslo musí mať aspoň 8 znakov.');
        $security->passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->securityRepo->save($security);
        return true;
    }
}
