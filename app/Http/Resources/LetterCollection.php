<?php

namespace App\Http\Resources;

use App\Models\Letter;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LetterCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $currentPage = $this->currentPage();
        $lastPage = $this->lastPage();
        $perPage = $this->perPage();
        $totalItemCount = $this->total();
        $currentItemCount = $this->count();

        if ($totalItemCount === 0) {
            $currentPageItems = 0;
        } else {
            if ($currentPage === 1) {
                $currentPageItems = "1 - $currentItemCount";
            } elseif ($currentPage === $lastPage) {
                $currentPageItems = (($currentPage - 1) * $perPage + 1) . " - $totalItemCount";
            } else {
                $currentPageItems = (($currentPage - 1) * $perPage + 1) . " - " . ($currentPage * $perPage);
            }
        }

        return [
            'data' => LetterResource::collection($this->collection),
            'meta' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'current_page_of_total' => "$currentPage / $lastPage",
                'per_page' => $perPage,
                'current_page_items' => $currentPageItems,
                'total_item_count' => $totalItemCount,
                'current_item_count' => $currentItemCount,
            ],
        ];
    }

    /**
     * Additional data to include with the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request): array
    {
        return [
            'collection' => config('app.name'),
        ];
    }
}
