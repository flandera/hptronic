<?php

declare(strict_types = 1);

namespace App\Service\Cart;

use App\Entity\Cart\Cart;
use App\Exception\Cart\CartNotFoundException;
use App\Exception\Cart\ProductNotFoundException;
use App\Repository\Cart\CartRepositoryInterface;
use App\Repository\Cart\ProductRepositoryInterface;

class CartService
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function createCart(): Cart
    {
        $id = $this->cartRepository->nextIdentity();
        $cart = new Cart($id);
        $this->cartRepository->save($cart);

        return $cart;
    }

    public function getCart(string $id): Cart
    {
        $cart = $this->cartRepository->find($id);

        if ($cart === null) {
            throw new CartNotFoundException(\sprintf('Cart with id "%s" not found.', $id));
        }

        return $cart;
    }

    public function addProduct(string $cartId, string $sku, int $quantity): Cart
    {
        $cart = $this->getCart($cartId);

        $product = $this->productRepository->findBySku($sku);

        if ($product === null) {
            throw new ProductNotFoundException(\sprintf('Product with SKU "%s" not found.', $sku));
        }

        $cart->addProduct($product, $quantity);
        $this->cartRepository->save($cart);

        return $cart;
    }

    public function removeProduct(string $cartId, string $sku, ?int $quantity): Cart
    {
        $cart = $this->getCart($cartId);

        $product = $this->productRepository->findBySku($sku);

        if ($product === null) {
            throw new ProductNotFoundException(\sprintf('Product with SKU "%s" not found.', $sku));
        }

        $cart->removeProduct($product, $quantity);
        $this->cartRepository->save($cart);

        return $cart;
    }
}
