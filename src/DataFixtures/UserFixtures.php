<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        try {
            // Création d'un utilisateur simple
            $user = new User();
            $user->setEmail('user@example.com');
            $user->setPassword($this->passwordHasher->hashPassword($user, 'userpassword'));
            $user->setRoles(['ROLE_USER']);
            $user->setNom('Utilisateur');
            $user->setPrenom('Test');
            $manager->persist($user);

            // Création d'un administrateur
            $admin = new User();
            $admin->setEmail('admin@example.com');
            $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setNom('Admin');
            $admin->setPrenom('Super');
            $manager->persist($admin);

            // Création d'un second administrateur pour tester plusieurs admins
            $superAdmin = new User();
            $superAdmin->setEmail('superadmin@example.com');
            $superAdmin->setPassword($this->passwordHasher->hashPassword($superAdmin, 'superpassword'));
            $superAdmin->setRoles(['ROLE_SUPER_ADMIN']);
            $superAdmin->setNom('SuperAdmin');
            $superAdmin->setPrenom('Master');
            $manager->persist($superAdmin);

            // Ajout de références pour d'autres fixtures si nécessaire
            $this->addReference('user-test', $user);
            $this->addReference('admin-test', $admin);
            $this->addReference('super-admin-test', $superAdmin);

            $manager->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors du chargement des fixtures : ' . $e->getMessage());
        }
    }
}
