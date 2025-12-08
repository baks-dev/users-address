<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Users\Address\UseCase\Geocode;

use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Users\Address\Entity\GeocodeAddress;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class GeocodeAddressHandler extends AbstractHandler
{
    public function handle(GeocodeAddressDTO $command): string|GeocodeAddress
    {
        $this->setCommand($command);

        /** Если найдена геолокация - возвращаем */
        $GeocodeAddress = $this
            ->getRepository(GeocodeAddress::class)
            ->findOneBy([
                'longitude' => $command->getLongitude(),
                'latitude' => $command->getLatitude(),
            ]);

        if($GeocodeAddress)
        {
            return $GeocodeAddress;
        }


        $GeocodeAddress = new GeocodeAddress();
        $GeocodeAddress->setEntity($command);

        $this->persist($GeocodeAddress);

        $this->validatorCollection->add($GeocodeAddress);


        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        try
        {
            /* Сохраняем */
            $this->flush();
        }
        catch(UniqueConstraintViolationException $exception)
        {
            return $exception->getMessage();
        }


        return $GeocodeAddress;
    }
}
