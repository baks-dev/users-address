<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace BaksDev\Users\Address\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Users\Profile\TypeProfile\Repository\AllProfileType\AllProfileTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_USERS_ADDRESS')]
final class IndexController extends AbstractController
{
    #[Route('/admin/users/address/{page<\d+>}', name: 'admin.index', methods: [
        'GET',
        'POST',
    ])]
    public function index(
        Request $request,
        AllProfileTypeInterface $allProfileType,
        int $page = 0,
    ): Response
    {

        dd('/admin/users/address');

        // Поиск
        $search = new Search\SearchDTO();
        $searchForm = $this->createForm(Search\SearchForm::class, $search);
        $searchForm->handleRequest($request);

        // Получаем список
        $query = $allProfileType->find($search);

        return $this->render(
            [
                'query' => $query,
                'search' => $searchForm->createView(),
            ]
        );
    }
}
