<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Transformers\ProductTransformer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

class ProductController extends Controller
{
    public function actionProducts(Request $request)
    {
        $user_id = $request->input('user_id');
        $category_id = $request->input('category_id');
        $search = $request->input('search');

        $product = new Product();

        if ($user_id) {
            $product = $product->where([
                ['user_id','=',$user_id]
            ]);
        }

        if ($category_id) {
            $product = $product->where(['category_id', '=', $category_id]);
        }

        if ($search) {
            $product = $product->where(['name', 'LIKE', '%' . $search . '%']);
        }

        $product = $product->orderBy('id', 'DESC')
            ->get();


        return fractal($product, new ProductTransformer())->toArray();
    }

    public function actionProduct($id)
    {
        $product = Product::find($id);

        if (!$product)
            return $this->responseUnprocessable(new MessageBag(['Product is not found']));

        return fractal($product, new ProductTransformer())->toArray();
    }

    public function actionProductStore(Request $request)
    {
        $validatedProductStore = Validator::make($request->all(), [
            'user_id' => 'required',
            'category_id' => 'required|integer',
            'name' => 'required|max:255',
            'photo' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validatedProductStore->fails()) {
            return $this->responseUnprocessable($validatedProductStore->errors());
        }

        try {
            $payload = [
                'user_id' => $request->input('user_id'),
                'category_id' => $request->input('category_id'),
                'name' => $request->input('name'),
                'kilometer' => $request->input('kilometer'),
                'color' => $request->input('color'),
                'machine' => $request->input('machine'),
                'passenger' => $request->input('passenger'),
                'transmission' => $request->input('transmission')
            ];

            if ($request->file('photo')) {
                // Upload photo
                $name = time() . '.' . $request->file('photo')->extension();
                $request->file('photo')->storeAs('product', $name);

                $payload['photo'] = asset('photo/product') . '/' . $name;
            }

            $product = new Product($payload);
            $product->save();

            return fractal($product, new ProductTransformer())->toArray();
        } catch (Exception $e) {
            return $this->responseServerError($e->getMessage());
        }
    }

    public function actionProductUpdate(Request $request)
    {
        $validatedProductUpdate = Validator::make($request->all(), [
            'user_id' => 'required',
            'category_id' => 'required|integer',
            'name' => 'required|max:255',
            'photo' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validatedProductUpdate->fails()) {
            return $this->responseUnprocessable($validatedProductUpdate->errors());
        }

        $product = Product::find($request->input('id'));

        if (!$product)
            return $this->responseUnprocessable(new MessageBag(['Product is not found']));

        try {
            $payload = [
                'user_id' => $request->input('user_id'),
                'category_id' => $request->input('category_id'),
                'name' => $request->input('name'),
                'kilometer' => $request->input('kilometer'),
                'color' => $request->input('color'),
                'machine' => $request->input('machine'),
                'passenger' => $request->input('passenger'),
                'transmission' => $request->input('transmission')
            ];

            if ($request->file('photo')) {
                // Check if any photo existing it will delete first
                if ($product->photo && File::exists(storage_path('app/product/' . basename($product->photo))))
                    File::delete(storage_path('app/product/' . basename($product->photo)));

                // Upload photo
                $name = time() . '.' . $request->file('photo')->extension();
                $request->file('photo')->storeAs('product', $name);

                $payload['photo'] = asset('photo/product') . '/' . $name;
            }

            $product->update($payload);

            return fractal($product, new ProductTransformer())->toArray();
        } catch (Exception $e) {
            return $this->responseServerError($e->getMessage());
        }
    }

    public function actionProductDelete($id)
    {
        $product = Product::find($id);

        if (!$product)
            return $this->responseUnprocessable(new MessageBag(['Product is not found']));

        // Check if any photo existing it will delete first
        if ($product->photo && File::exists(storage_path('app/product/' . basename($product->photo))))
            File::delete(storage_path('app/product/' . basename($product->photo)));

        $product->delete();

        return $this->responseSuccess('Product deleted');
    }
}
