<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductController extends AbstractController
{
    #[Route('/product', name: 'product_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $products = $em->getRepository(Product::class)->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/product/new', name: 'product_new')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleFileUpload($form, $product, $slugger);

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/product/edit/{id}', name: 'product_edit')]
    public function edit(Product $product, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleFileUpload($form, $product, $slugger);

            $em->flush();

            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', ['product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/product/delete/{id}', name: 'product_delete', methods: ['POST'])]
public function delete(Request $request, Product $product, EntityManagerInterface $em): Response
{
    // Validate the CSRF token to prevent CSRF attacks
    if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
        $em->remove($product);
        $em->flush();

        // Flash success message after deletion
        $this->addFlash('success', 'Product deleted successfully!');
    } else {
        // Flash error message if CSRF token is invalid
        $this->addFlash('error', 'Invalid CSRF token.');
    }

    // Redirect back to the product list page
    return $this->redirectToRoute('product_index');
}

    private function handleFileUpload($form, Product $product, SluggerInterface $slugger)
    {
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );
                $product->setImage($newFilename);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Image upload failed: ' . $e->getMessage());
            }
        }
    }
}