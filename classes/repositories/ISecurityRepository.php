<?php
interface ISecurityRepository {
    public function findByUser(int $userId): ?Security;
    public function findById(int $id): ?Security;
    public function save(Security $security): Security;
}
