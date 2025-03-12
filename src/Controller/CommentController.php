<?php

namespace App\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comment')]
class CommentController extends AbstractController
{
    #[Route('/delete/{id}', name: 'comment_delete', methods: ['POST'])]
    public function deleteComment(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $comment = $entityManager->getRepository(Comment::class)->find($id);

        if (!$comment) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        // Vérifie la validité du token CSRF
        if ($this->isCsrfTokenValid('delete-comment' . $comment->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();

            // Vérifie que l'utilisateur est l'auteur ou administrateur
            if ($user === $comment->getUser() || $this->isGranted('ROLE_ADMIN')) {
                $entityManager->remove($comment);
                $entityManager->flush();
                $this->addFlash('success', 'Commentaire supprimé avec succès.');
            } else {
                throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce commentaire.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('product_show', ['id' => $comment->getProduct()->getId()]);
    }
}
