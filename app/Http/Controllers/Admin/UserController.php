<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\UserDataTable;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreRequest;
use App\Models\Country;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Dashboard CRUD for players.
 *
 * This controller talks to `UserRepository` (which inherits create / update /
 * delete from `BaseRepository`) rather than to `UserService`. `UserService` is
 * the API-side profile/ranking service and has never carried admin CRUD
 * methods; the original scaffold was copied from `CountryController` and called
 * `getFormData()`, `create()`, `update()` and `delete()` on it, none of which
 * exist — every one of those calls raised
 * `BadMethodCallException: Call to undefined method App\Services\UserService::…`
 * and that is what made "Add user" blow up before the form even rendered.
 */
class UserController extends Controller
{
    public function __construct(protected UserRepository $users)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(UserDataTable $dataTable)
    {
        return $dataTable->render('dashboard.users.index');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return view('dashboard.users.show', ['user' => $user->loadMissing('country')]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.users.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        try {
            $this->users->create($this->payload($request));
        } catch (Throwable $exception) {
            return $this->saveFailed($exception);
        }

        added();

        return redirect()->route('admin.users.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return view('dashboard.users.edit', array_merge(
            ['user' => $user],
            $this->formData()
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, User $user)
    {
        try {
            $this->users->update($user, $this->payload($request));
        } catch (Throwable $exception) {
            return $this->saveFailed($exception);
        }

        updated();

        return redirect()->route('admin.users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            $this->users->delete($user);
        } catch (QueryException $exception) {
            // `groups.user_id` is a RESTRICT foreign key, so deleting a group
            // owner raises SQLSTATE 23000 (1451). Surface that as a readable
            // message instead of a 500 page.
            Log::warning('Admin user delete blocked by a database constraint.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            alert()->error(__('cannot delete user linked to records'));

            return back();
        } catch (Throwable $exception) {
            Log::error('Admin user delete failed.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            alert()->error(__('an error occurred, please try again'));

            return back();
        }

        deleted();

        return back();
    }

    /**
     * Build the attribute list that is actually written to `users`.
     *
     * `full_phone` is a STORED generated column and is never part of this array.
     * The avatar is only included when a NEW file was really uploaded, so saving
     * the edit form without re-picking an image keeps the current one instead of
     * letting the `setImageAttribute` mutator overwrite it with null.
     *
     * The nullable text fields behave the opposite way on purpose: clearing
     * `gender`, `nickname` or the country select posts an empty string, which
     * `ConvertEmptyStringsToNull` turns into null. `validated()` keeps keys whose
     * value is null, so "cleared" really clears instead of silently keeping the
     * previous value.
     *
     * @return array<string, mixed>
     */
    private function payload(StoreRequest $request): array
    {
        $data = $request->validated();

        unset($data['image']);

        if ($request->hasFile('image')) {
            // Passing the UploadedFile through is deliberate: the model's
            // `AvatarOperations::setImageAttribute` mutator runs `uploader()`
            // on it, exactly as the countries / tips forms already do.
            $data['image'] = $request->file('image');
        }

        return $data;
    }

    /**
     * Shared select options for the create + edit forms.
     *
     * @return array<string, array<string, mixed>>
     */
    private function formData(): array
    {
        // Built from LOADED models, never `pluck()` on the query builder:
        // `countries.name` is a spatie translatable JSON column, and a
        // query-level pluck would push the raw {"ar":…,"en":…} blob into the
        // select instead of the localized name.
        $countries = Country::query()->orderBy('id')->get();

        $phoneCodes = [];
        $countryOptions = ['' => __('not specified')];

        foreach ($countries as $country) {
            $name = $this->countryName($country);
            $code = (string) $country->country_code;

            if ($code !== '') {
                $phoneCodes[$code] = '+' . ltrim($code, '+') . ' - ' . $name;
            }

            $countryOptions[$country->id] = $name;
        }

        return [
            'phoneCodes' => $phoneCodes,
            'countries' => $countryOptions,
            'genders' => [
                '' => __('not specified'),
                'male' => __('male'),
                'female' => __('female'),
            ],
            'languages' => [
                'ar' => __('arabic'),
                'en' => __('english'),
            ],
            'statuses' => collect(UserStatus::cases())
                ->mapWithKeys(fn (UserStatus $status) => [$status->value => __($status->value)])
                ->all(),
            // Mirrors the column defaults in `create_users_table` so a brand new
            // row starts out exactly as the API would have created it.
            'defaults' => [
                'status' => UserStatus::ONLINE->value,
                'language' => 'en',
            ],
        ];
    }

    /**
     * Localized country name, falling back to the raw column and then to the
     * phone code so a row saved before translations were introduced still shows
     * something selectable rather than an empty option.
     */
    private function countryName(Country $country): string
    {
        $name = $country->name;

        if (is_string($name) && $name !== '') {
            return $name;
        }

        $raw = (string) ($country->getRawOriginal('name') ?? '');

        return $raw !== '' ? $raw : (string) $country->country_code;
    }

    /**
     * Turn an unexpected save failure into a localized alert plus the form with
     * the operator's input still in it, instead of a raw exception page.
     */
    private function saveFailed(Throwable $exception)
    {
        Log::error('Admin user save failed.', [
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
        ]);

        alert()->error(__('an error occurred, please try again'));

        return back()->withInput();
    }
}
