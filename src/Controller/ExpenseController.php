<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Form\ExpenseType;
use App\Repository\ExpenseRepository;
use App\Repository\TricountRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/expense')]
class ExpenseController extends AbstractController
{
    private TricountRepository $tricountRepository;

    public function __construct(TricountRepository $tricountRepository)
    {
        $this->tricountRepository = $tricountRepository;

    }

    #[Route('/{param}', name: 'expense_index', methods: ['GET'])]
    public function index(int $param, ExpenseRepository $expenseRepository, LoggerInterface $logger): Response
    {
        $expenses = $this->tricountRepository->find($param)->getExpenses();
        $payor_names = [];
        foreach ($expenses as $expense)
        {
            array_push($payor_names, $payor = $expense->getPayer()->getName());
        }
        return $this->render('expense/index.html.twig', [
            'expenses' => $expenses,
            'param' => $param,
            'payor_names' => $payor_names,
        ]);
    }

    #[Route('/new/{param}', name: 'expense_new', methods: ['GET', 'POST'])]
    public function new(int $param, Request $request, EntityManagerInterface $entityManager, MailService $mailService): Response
    {

        $tricount = $this->tricountRepository->find($param);

        $expense = new Expense();
        $expense->setTricount($tricount);
        $form = $this->createForm(ExpenseType::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($expense);
            $entityManager->flush();
            $mailService->sendNotification();

            return $this->redirectToRoute('expense_index', ['param' => $param], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('expense/new.html.twig', [
            'expense' => $expense,
            'form' => $form,
            'param' => $param,
        ]);
    }

    #[Route('/show/{id}/{param}', name: 'expense_show', methods: ['GET'])]
    public function show(Expense $expense, int $param): Response
    {
        return $this->render('expense/show.html.twig', [
            'expense' => $expense,
            'param' => $param
        ]);
    }

    #[Route('/edit/{id}/{param}', name: 'expense_edit', methods: ['GET', 'POST'])]
    public function edit(int $param, Request $request, Expense $expense, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ExpenseType::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('expense_index', ['param'=> $param], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('expense/edit.html.twig', [
            'expense' => $expense,
            'form' => $form,
            'param' => $param,
        ]);
    }

    #[Route('/{id}', name: 'expense_delete', methods: ['POST'])]
    public function delete(Request $request, Expense $expense, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$expense->getId(), $request->request->get('_token'))) {
            $entityManager->remove($expense);
            $entityManager->flush();
        }

        return $this->redirectToRoute('expense_index', [], Response::HTTP_SEE_OTHER);
    }
}






