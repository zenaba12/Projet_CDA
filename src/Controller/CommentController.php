<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Product;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
class CommentController extends AbstractController
{
    #[Route('/add/{productId}', name: 'comment_add', methods: ['POST'])]
    public function addComment(
        int $productId,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer le produit
        $product = $entityManager->getRepository(Product::class)->find($productId);
        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Récupérer le contenu du commentaire depuis la requête
        $data = json_decode($request->getContent(), true);
        if (!isset($data['contenu']) || empty(trim($data['contenu']))) {
            return $this->json(['error' => 'Le commentaire ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        // Créer et enregistrer le commentaire
        $comment = new Comment();
        $comment->setContenu($data['contenu']);
        $comment->setUser($user);
        $comment->setProduct($product);

        $entityManager->persist($comment);
        $entityManager->flush();

        return $this->json(['message' => 'Commentaire ajouté avec succès']);
    }

    #[Route('/delete/{id}', name: 'comment_delete', methods: ['DELETE'])]
    public function deleteComment(
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        // Récupérer le commentaire
        $comment = $entityManager->getRepository(Comment::class)->find($id);
        if (!$comment) {
            return $this->json(['error' => 'Commentaire non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur connecté est le propriétaire du commentaire ou un administrateur
        $user = $this->getUser();
        if (!$user || ($comment->getUser() !== $user && !in_array('ROLE_ADMIN', $user->getRoles()))) {
            return $this->json(['error' => 'Vous ne pouvez pas supprimer ce commentaire'], Response::HTTP_FORBIDDEN);
        }

        // Supprimer le commentaire
        $entityManager->remove($comment);
        $entityManager->flush();

        return $this->json(['message' => 'Commentaire supprimé avec succès']);
    }

    #[Route('/product/{productId}', name: 'comment_list', methods: ['GET'])]
    public function listComments(int $productId, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le produit
        $product = $entityManager->getRepository(Product::class)->find($productId);
        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Récupérer les commentaires du produit
        $comments = $product->getComments()->map(fn($comment) => [
            'id' => $comment->getId(),
            'contenu' => $comment->getContenu(),
            'auteur' => $comment->getUser() ? $comment->getUser()->getEmail() : 'Utilisateur supprimé',
            'date' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
        ])->toArray();

        return $this->json($comments);
    }
}
