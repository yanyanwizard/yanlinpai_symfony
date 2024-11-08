<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface; // For pagination
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Timezone;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Intl\Timezones; 
use Symfony\Component\HttpFoundation\JsonResponse;  
use DateTimeImmutable;
use DateTimeZone; 
use App\Form\ProductImportType; 
use Symfony\Component\HttpFoundation\File\UploadedFile; 
use League\Csv\Reader; 
use League\Csv\Writer;

#[Route('/product')]
final class ProductController extends AbstractController
{

    
    #[Route(name: 'product_index',methods: ['GET', 'POST'])]
    public function index(): Response
    {
        $csrfTokenManager = $this->container->get('security.csrf.token_manager');
        return $this->render('product/index.html.twig');
    }
     

    #[Route('/data', name: 'products_data', methods: ['GET'])]
    public function getData(ProductRepository $productRepository): JsonResponse
    {

        $csrfTokenManager = $this->container->get('security.csrf.token_manager');
        
        $products = $productRepository->findAll();
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'stock' => $product->getStockQuantity(),
                'created_at' => $product->getCreatedAt()->format('Y-m-d H:i:s'),
                'editUrl' => $this->generateUrl('product_edit', ['id' => $product->getId()]),
                'deleteUrl' => $this->generateUrl('product_delete', ['id' => $product->getId()]),
                'csrfToken' => $csrfTokenManager->getToken('delete' . $product->getId())->getValue(),
            ];
        }

        return new JsonResponse(['data' => $data]);

        
    }

    


    #[Route('/{id}/edit', name: 'product_edit',methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $product->setCreatedAt(new DateTimeImmutable('now', new DateTimeZone('UTC')));
       

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save the updated product data
            $entityManager->flush();

            // Add a flash message or redirect as needed
            $this->addFlash('success', 'Product updated successfully.');

            return $this->redirectToRoute('product_index'); // or to another route as needed
        }

        
        // Check if request is AJAX, if so, render only the form
        if ($request->isXmlHttpRequest()) {
            return $this->render('product/edit.html.twig', [
                'product' => $product,
                'form' => $form->createView(),
            ]);
        } 
       
        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'product_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        
       
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
             echo 1;
            $this->addFlash('success', 'Product deleted successfully.');
        } else {
            echo 2;
            $this->addFlash('error', 'Invalid CSRF token.');
        } 
        return $this->redirectToRoute('product_index');
    }


    public function registerBundles()
    {
        // After Symfony's own bundles 
        new \Omines\DataTablesBundle\DataTablesBundle();
        // Before your application bundles
    }
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('timezone', new Assert\Timezone());
    }
 
    
    

    #[Route('/import', name: 'product_import', methods: ['GET', 'POST'])] 
    public function import(Request $request,  EntityManagerInterface $entityManager): Response
    {
        
        $form = $this->createForm(ProductImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $csvFile = $form->get('csvFile')->getData();

            if ($csvFile) {
                try {
                    $filePath = $csvFile->getRealPath();
                    $csv = Reader::createFromPath($filePath, 'r');
                    $csv->setHeaderOffset(0); // Assumes the first row is the header

                    foreach ($csv as $record) {
                        $product = new Product();
                        $product->setName($record['Name']);
                        $product->setDescription($record['Description']);
                        $product->setPrice((float) $record['Price']);
                        $product->setStockQuantity((int) $record['Stock Quantity']);
                        $product->setCreatedAt(new \DateTimeImmutable($record['Created At']));

                        $entityManager->persist($product);
                    }

                    $entityManager->flush();
                    $this->addFlash('success', 'Products imported successfully!');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error processing the file.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'An error occurred during import.');
                }
            }

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }
 
    #[Route('/export', name: 'product_export', methods: ['GET'])]
    public function export(EntityManagerInterface $entityManager): Response
    {
        // Retrieve all products from the database
        $products = $entityManager->getRepository(Product::class)->findAll();

        // Create CSV writer
        $csv = Writer::createFromString('');
        $csv->insertOne(['ID', 'Name', 'Description', 'Price', 'Stock Quantity', 'Created At']); // CSV headers

        // Loop through products to add rows to the CSV
        foreach ($products as $product) {
            $csv->insertOne([
                $product->getId(),
                $product->getName(),
                $product->getDescription(),
                $product->getPrice(),
                $product->getStockQuantity(),
                $product->getCreatedAt()->format('Y-m-d H:i:s'),
            ]);
        }

        // Prepare CSV response for download
        $response = new Response($csv->getContent());
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="products.csv"');
        
        return $response;
    }
 
    #[Route('/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {

        
        $product = new Product();
        $product->setCreatedAt(new DateTimeImmutable('now', new DateTimeZone('UTC')));
       
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            // Return errors if the form is invalid
            return new JsonResponse([
                'status' => 'error',
                'form' => $this->renderView('product/new.html.twig', [
                    'form' => $form->createView(),
                ]),
                'errors' => (string) $form->getErrors(true, false), // Capture form errors
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            // return new JsonResponse([
            //     'status' => 'success',
            //     'message' => 'Product added successfully!',
            // ]);

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function importFromCsv(Request $request, EntityManagerInterface $em): Response
    {
        // Logic to upload and parse CSV, then insert records into the database
    }

    // Export products to CSV
    public function exportToCsv(ProductRepository $repository): Response
    {
        // Logic to query products and generate a downloadable CSV file
    }
}
