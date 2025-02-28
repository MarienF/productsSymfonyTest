<?php
// tests/Controller/ProductApiControllerTest.php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductApiControllerTest extends WebTestCase
{
    public function testGetProducts(): void
    {
        // Crée un client HTTP pour simuler les requêtes
        $client = static::createClient();
		$userRepository = static::getContainer()->get(UserRepository::class);
		$testUser = $userRepository->findOneByEmail('admin@example.com');
		
		if (!$testUser) {
			$testUser = new User();
			$testUser->setEmail('admin@example.com');
			$testUser->setRoles(['ROLE_ADMIN']);
			$passwordHasher = static::getContainer()->get('security.user_password_hasher');
			$testUser->setPassword($passwordHasher->hashPassword($testUser, 'password'));
			
			$entityManager = static::getContainer()->get('doctrine')->getManager();
			$entityManager->persist($testUser);
			$entityManager->flush();
		}
    
		// 2. Simuler la connexion
		$client->loginUser($testUser);
        
        // Envoie une requête GET à l'API
        $client->request('GET', '/api/products/search');
        
        // Vérifie que la réponse a un statut 200 (OK)
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        
        // Vérifie que le contenu est au format JSON
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );
        
        // Décode le contenu JSON
        $data = json_decode($client->getResponse()->getContent(), true);
        
        // Vérifie que les données sont dans le bon format
        $this->assertIsArray($data);
    }
    
    public function testCreateProduct(): void
    {
        // Crée un client HTTP
        $client = static::createClient();
		$userRepository = static::getContainer()->get(UserRepository::class);
		$testUser = $userRepository->findOneByEmail('admin@example.com');
		
		if (!$testUser) {
			$testUser = new User();
			$testUser->setEmail('admin@example.com');
			$testUser->setRoles(['ROLE_ADMIN']);
			$passwordHasher = static::getContainer()->get('security.user_password_hasher');
			$testUser->setPassword($passwordHasher->hashPassword($testUser, 'password'));
			
			$entityManager = static::getContainer()->get('doctrine')->getManager();
			$entityManager->persist($testUser);
			$entityManager->flush();
		}
    
		// 2. Simuler la connexion
		$client->loginUser($testUser);
        
        // Données pour créer un nouveau produit
        $newProductData = [
            'name' => 'Produit de test',
            'price' => 99.99,
            'description' => 'Description du produit de test'
        ];
        
        // Envoie une requête POST avec les données
        $client->request(
            'POST',
            '/api/products/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($newProductData)
        );
        
        // Vérifie que la création a réussi
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        
        // Vérifie que la réponse contient la confirmation
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        
        // Vérifie que le produit a bien été inséré en base de données
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $productRepository = $entityManager->getRepository(Product::class);
        
        $product = $productRepository->findOneBy(['name' => 'Produit de test']);
        $this->assertNotNull($product);
        $this->assertEquals(99.99, $product->getPrice());
    }
}