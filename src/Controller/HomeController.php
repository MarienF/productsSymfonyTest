<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/accueil1234', name: 'app_home')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
		$product = new Product();
		$form = $this->createForm(ProductType::class, $product);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
				$entityManager->persist($product);
				$entityManager->flush();

				$this->addFlash('success', 'Produit ajouté avec succès !');
        	    return $this->redirectToRoute('app_home');
		}

		$searchTerm = $request->query->get('search', '');

		$products = $entityManager->getRepository(Product::class)
        ->createQueryBuilder('p')
        ->where('p.name LIKE :search OR p.description LIKE :search')
        ->setParameter('search', '%'.$searchTerm.'%')
        ->getQuery()
        ->getResult();

        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
            'products' => $products,
    	    'searchTerm' => $searchTerm
        ]);
    }


	#[Route('api/products/add', name: 'api_add_product')]
	#[IsGranted('ROLE_USER')]
	public function addProduct(Request $request, EntityManagerInterface $entityManager): Response
	{
		$data = json_decode($request->getContent(), true);
		
		// Validation manuelle
		$errors = [];
		if (strlen($data['name']) < 3) {
			$errors[] = "Le nom doit faire au moins 3 caractères";
		}
		if ($data['price'] <= 0) {
			$errors[] = "Le prix doit être positif";
		}
		if (strlen($data['description']) < 10) {
			$errors[] = "La description doit faire plus de 10 caractères";
		}
		if (strlen($data['description']) > 255) {
			$errors[] = "La description doit faire moins de 255 caractères";
		}
	
		if (count($errors) > 0) {
			return new JsonResponse([
				'success' => false,
				'errors' => $errors
			]);
		}

		$product = new Product();
		$product->setName($data['name']);
		$product->setPrice($data['price']);
		$product->setDescription($data['description']);

		$entityManager->persist($product);
		$entityManager->flush();
		
		return new JsonResponse([
			'success' => true,
			'product' => [
				'id' => $product->getId(),
				'name' => $product->getName(),
				'price' => $product->getPrice(),
				'description' => $product->getDescription()
			]
		]);
	}


	#[Route('/api/products/search', name: 'api_search_products')]
	#[IsGranted('ROLE_USER')]
	public function searchProducts(Request $request, EntityManagerInterface $entityManager): Response
	{
		$searchTerm = $request->query->get('search', '');
		
		$products = $entityManager->getRepository(Product::class)
			->createQueryBuilder('p')
			->where('p.name LIKE :search OR p.description LIKE :search')
			->setParameter('search', '%'.$searchTerm.'%')
			->getQuery()
			->getResult();

		// Formatage des données pour JSON
		$formattedProducts = array_map(function($product) {
			return [
				'id' => $product->getId(),
				'name' => $product->getName(),
				'price' => $product->getPrice(),
				'description' => $product->getDescription()
			];
		}, $products);

		return new JsonResponse($formattedProducts);
	}

	
	#[Route('api/products/delete/{id}', name: 'api_delete_product')]
	//#[IsGranted('ROLE_ADMIN', message: 'Vous devez être administrateur pour modifier un produit')]
	public function deleteProduct(Product $product, EntityManagerInterface $entityManager): Response
	{
		if (!$this->isGranted('ROLE_ADMIN')) {
			return new JsonResponse([
				'success' => false,
				'error' => 'Accès refusé. Vous devez être administrateur pour effectuer cette action.'
			], 403);
		}

		$entityManager->remove($product);
		$entityManager->flush();
		
		return new JsonResponse([
			'success' => true
		]);
	}


	#[Route('api/products/update/{id}', name: 'api_update_product')]
	#[IsGranted('ROLE_USER')]
	public function updateProduct(Request $request, Product $product, EntityManagerInterface $entityManager): Response
	{
		$data = json_decode($request->getContent(), true);

		// Validation manuelle
		$errors = [];
		if (strlen($data['name']) < 3) {
			$errors[] = "Le nom doit faire au moins 3 caractères";
		}
		if ($data['price'] <= 0) {
			$errors[] = "Le prix doit être positif";
		}
		if (strlen($data['description']) < 10) {
			$errors[] = "La description doit faire au moins 10 caractères";
		}
	
		if (count($errors) > 0) {
			return new JsonResponse([
				'success' => false,
				'errors' => $errors
			]);
		}

		$product->setName($data['name']);
		$product->setPrice($data['price']);
		$product->setDescription($data['description']);
		
		$entityManager->flush();

		return new JsonResponse([
			'success' => true,
			'product' => [
				'id' => $product->getId(),
				'name' => $product->getName(),
				'price' => $product->getPrice(),
				'description' => $product->getDescription()
			]
		]);
	}
}