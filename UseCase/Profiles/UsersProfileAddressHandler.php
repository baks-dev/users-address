<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

declare(strict_types=1);

namespace BaksDev\Users\Address\UseCase\Profiles;

use BaksDev\Users\Address\Entity\UsersProfileAddress;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UsersProfileAddressHandler
{
    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    public function handle(
        UsersProfileAddressDTO $command,
    ): string|UsersProfileAddress {
        /**
         *  Валидация.
         */
        $errors = $this->validator->validate($command);

        if (count($errors) > 0)
        {
            $uniqid = uniqid('', false);
            $errorsString = (string) $errors;
            $this->logger->error($uniqid.': '.$errorsString);
            return $uniqid;
        }


        $UsersProfileAddress = $this->entityManager->getRepository(UsersProfileAddress::class)->findOneBy(
            ['address' => $command->getAddress(), 'profile' => $command->getProfile()]
        );

        if (!$UsersProfileAddress)
        {
            $UsersProfileAddress = new UsersProfileAddress();
            $UsersProfileAddress->setEntity($command);

            /* Валидация */
            $errors = $this->validator->validate($UsersProfileAddress);

            if (count($errors) > 0)
            {
                $uniqid = uniqid('', false);
                $errorsString = (string) $errors;
                $this->logger->error($uniqid.': '.$errorsString);
                return $uniqid;
            }

            $this->entityManager->clear();
            $this->entityManager->persist($UsersProfileAddress);
            $this->entityManager->flush();
        }

        return $UsersProfileAddress;
    }
}
