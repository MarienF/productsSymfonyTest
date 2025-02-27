<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un Admin',
)]
class CreateAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Cette commande vous permet de créer un utilisateur avec le rôle ROLE_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Demander l'email et le mot de passe
        $email = $io->ask('Email de l\'administrateur');
        $password = $io->askHidden('Mot de passe');
        
        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        
        // Encoder le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        // Sauvegarder en base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $io->success('Administrateur créé avec succès!');
        
        return Command::SUCCESS;
    }
}
