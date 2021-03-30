<?php

namespace App\Http\Controllers\Front;

//use App\MicroApi\Services\UserService;
use App\Services\Customer\UserService;
use App\Shop\Couriers\Repositories\Interfaces\CourierRepositoryInterface;
use App\Shop\Customers\Repositories\CustomerRepository;
use App\Shop\Customers\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Shop\Orders\Order;
use App\Shop\Orders\Transformers\OrderTransformable;

class AccountsController extends Controller
{
    use OrderTransformable;

    /**
     * @var CustomerRepositoryInterface
     */
//    private $customerRepo;

    /**
     * @var CourierRepositoryInterface
     */
//    private $courierRepo;
    /**
     * @var UserService
     */
    private  $userService;
    /**
     * AccountsController constructor.
     *
//     * @param CourierRepositoryInterface $courierRepository
//     * @param CustomerRepositoryInterface $customerRepository
     * @param UserService $userService
     */
    public function __construct(
//        CourierRepositoryInterface $courierRepository,
//        CustomerRepositoryInterface $customerRepository,
        UserService $userService
    ) {
//        $this->customerRepo = $customerRepository;
//        $this->courierRepo = $courierRepository;
        $this->userService = $userService;
    }

    public function index()
    {
        $customer = auth()->user();
//        $customer = $this->customerRepo->findCustomerById(auth()->user()->id);

//        $customerRepo = new CustomerRepository($customer);

        $orders = $this->userService->getPaginatedOrdersByUserId($customer->id);

//        $orders = $customerRepo->findOrders(['*'], 'created_at');

        $orders->transform(function (Order $order) {
            return $this->transformOrder($order);
        });
//
//        $orders->load('products');

//        $addresses = $customerRepo->findAddresses();
        $addresses = $this->userService->getAddressesByUserId($customer->id);
        return view('front.accounts', [
            'customer' => $customer,
            'orders' => $orders,
            'addresses' => $addresses
        ]);
    }


}
