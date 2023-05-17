<?php
/*
 *  Copyright Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Users\Address\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Users\Profile\TypeProfile\Repository\AllProfileType\AllProfileTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    ): Response {
        dd('/admin/users/address');

        // Поиск
        $search = new Search\SearchDTO();
        $searchForm = $this->createForm(Search\SearchForm::class, $search);
        $searchForm->handleRequest($request);

        // Получаем список
        $query = $allProfileType->get($search);

        return $this->render(
            [
                'query' => $query,
                'search' => $searchForm->createView(),
            ]
        );
    }
}
