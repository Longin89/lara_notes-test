<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */

// Метод вывода данных

    public function index(Request $request)
    {
        try {

// Получение параметров запроса

            $page = $request->query('page', 1);
            $perPage = 2;

// Получение данных в массив

            $notebooks = Note::paginate($perPage)->toArray();

// Формирование ответа, если все хорошо

            return response()->json([
                'data' => $notebooks['data'],
                'meta' => [
                    'CurrentPage' => $notebooks['current_page'],
                    'LastPage' => $notebooks['last_page'],
                    'PerPage' => $perPage,
                    'Total' => $notebooks['total'],
                ],
                'status' => 200
            ]);

// если нет - выводим соответствующую ошибку

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Внутренняя ошибка сервера',
                'message' => $e->getMessage(),
                'status' => $e->getCode()
            ], $e->getCode());
        }
    }

// Метод, созданные при инициализации проекта, не используется.

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

// Метод добавления в базу

    public function store(Request $request)
    {

// Здесь происходит валидация входных данных

        try {
            $validatedData = $request->validate([
                'fio' => 'required|string:max:255',
                'phone' => 'required|integer:max:20',
                'email' => 'required|email',
                'company' => 'nullable|string|max:255',
                'birthday' => 'nullable|date',
            ]);

// Если будет загружено фото - вызываем соответствующую функцию

            if ($request->hasFile('photo')) {
                $validatedData['photo'] = $this->uploadPhoto($request->file('photo'), $request->input('fio'));
            }

// Здесь создается новая запись в базе данных с помощью модели Note и валидированными данными.

            $notebook = Note::create($validatedData);

            return response()->json([
                'data' => $notebook,
                'message' => 'Запись успешно создана',
                'status' => 201
            ], 201);
        } catch (\Exception $e) {

// Проверяем, является ли исключение связанным с валидацией

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'error' => 'Некорректные данные при отправке запроса',
                    'message' => $e->getMessage(),
                    'status' => $e->getCode()
                ], $e->getCode());
            }

// Если возникла иная ошибка - информируем соответствующим кодом

            return response()->json([
                'error' => 'Внутренняя ошибка сервера',
                'message' => $e->getMessage(),
                'status' => $e->getCode()
            ], $e->getCode());
        }
    }

    /**
     * Display the specified resource.
     */

// Метод вывода отдельной записи

    public function show($id)
    {

// Пробуем найти запись по ид и вывести ее

        try {
            $notebook = Note::find($id);

// Если запись не найдена - выводим соответствующую ошибку

            if (!$notebook) {
                return response()->json([
                    'error' => 'Not found',
                    'message' => 'Запись с указанным ID не найдена',
                    'status' => 404
                ], 404);
            }

// Если запись найдена - выводим соответствующее сообщение и запись

            return response()->json([
                'content' => $notebook,
                'message' => 'Успешный вывод элемента',
                'status' => 200
            ], 200);

// Выводим соответствующую ошибку если что-то пошло не так

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Внутренняя ошибка сервера',
                'message' => $e->getMessage(),
                'status' => $e->getCode()
            ], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */

// Метод, созданные при инициализации проекта, не используется.

    public function edit(Note $note)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */

// Метод для обновления отдельной записи

    public function update(Request $request, $id)
    {
        try {

// Проверяем наличие записи с указанным ID и выводим, если они найдены

            $notebook = Note::find($id);
            if (!$notebook) {
                return response()->json([
                    'error' => 'Not found',
                    'message' => 'Запись не найдена',
                    'status' => 404
                ], 404);
            }

// Валидируем входные данные

            $validatedData = $request->validate([
                'fio' => 'required|string:max:255',
                'phone' => 'required|integer:max:20',
                'email' => 'required|email',
                'company' => 'nullable|string|max:255',
                'birthday' => 'nullable|date',
            ]);

// Обновляем только те поля, которые были отправлены в запросе

            $notebook->update(array_intersect_key($validatedData, $notebook->getAttributes()));
            return response()->json([
                'data' => $notebook,
                'message' => 'Запись успешно обновлена',
                'status' => 200
            ], 200);

// Если были введены некоректные данные - выводим ошибку

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Некорректные данные при отправке запроса',
                'message' => $e->getMessage(),
                'status' => $e->getCode(),
            ], $e->getCode());

// Выводим соответствующую ошибку если что-то пошло не так

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Внутренняя ошибка сервера',
                'message' => $e->getMessage(),
                'status' => $e->getCode()
            ], $e->getCode());
        }
    }

    /**
     * Remove the specified resource from storage.
     */

// Метод для удаления отдельной записи


    public function destroy($id)
    {

// Ищем указанную запись, если ее нет - выводим ошибку


        $notebook = Note::find($id);

        if (!$notebook) {
            return response()->json([
                'error' => 'Not found',
                'message' => "Запись не найдена",
                'status' => 404
            ], 404);
        }

        try {
            $notebook->delete();

            return response()->noContent();

// Выводим соответствующую ошибку если что-то пошло не так

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Внутренняя ошибка сервера',
                'message' => $e->getMessage(),
                'status' => $e->getCode()
            ], $e->getCode());
        }
    }

// Метод для загрузки изображений

    private function uploadPhoto($image, $id)
    {
// Генерируем уникальное имя файла
        $fileName = $id . '_photo_' . time() . '.' . $image->getClientOriginalExtension();

// Определяем путь для сохранения файла
        $folderPath = "public/photos/{$id}";

// Создаем директорию, если она не существует
        Storage::makeDirectory($folderPath, ['mode' => 0755]);

// Сохраняем файл в указанную папку с уникальным именем
        $path = Storage::putFileAs($folderPath, $image, $fileName);

// Возвращаем URL-адрес загруженного файла
        return Storage::url($path);
    }
}
