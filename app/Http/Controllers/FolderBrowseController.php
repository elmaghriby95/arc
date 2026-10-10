<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FolderBrowseController extends Controller
{
    public function index(Request $request, TransactionScopeService $scope): View
    {
        $user = $request->user();
        $term = $this->searchTerm($request);
        $folders = $this->visibleFolders($user, $scope);

        if ($term === '') {
            $accessibleIds = $folders->pluck('id')->flip();
            $folders = $folders
                ->filter(fn (Folder $folder) => $folder->parent_id === null || ! isset($accessibleIds[$folder->parent_id]))
                ->values();
        } else {
            $folders = $folders
                ->filter(fn (Folder $folder) => $this->folderMatches($folder, $term))
                ->values();
        }

        return view('folders.index', [
            'folders' => $folders,
        ]);
    }

    public function show(Request $request, Folder $folder, TransactionScopeService $scope): View
    {
        $user = $request->user();

        if (! $folder->is_active || ! $user->canAccessFolder($folder)) {
            abort(403);
        }

        $folder->load('department');
        $term = $this->searchTerm($request);

        $children = $this->visibleFolders($user, $scope)
            ->where('parent_id', $folder->id)
            ->filter(fn (Folder $child) => $this->folderMatches($child, $term))
            ->values();

        $transactions = Transaction::query();
        $scope->applyScopeToQuery($transactions, $user);

        $transactions = $transactions
            ->with(['department', 'transactionType', 'status'])
            ->where('folder_id', $folder->id)
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function ($builder) use ($like) {
                    $builder->where('title', 'like', $like)
                        ->orWhere('reference_number', 'like', $like)
                        ->orWhere('archival_reference', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $ancestors = $this->ancestors($folder, $user);
        $parentFolder = $ancestors === [] ? null : $ancestors[array_key_last($ancestors)];

        return view('folders.browse', [
            'folder' => $folder,
            'ancestors' => $ancestors,
            'parentFolder' => $parentFolder,
            'children' => $children,
            'transactions' => $transactions,
        ]);
    }

    /** @return list<Folder> */
    private function ancestors(Folder $folder, User $user): array
    {
        $chain = [];
        $parentId = $folder->parent_id;
        $guard = 0;

        while ($parentId && $guard < 30) {
            $parent = Folder::query()->find($parentId);

            if (! $parent || ! $parent->is_active || ! $user->canAccessFolder($parent)) {
                break;
            }

            array_unshift($chain, $parent);
            $parentId = $parent->parent_id;
            $guard++;
        }

        return $chain;
    }

    private function visibleFolders(User $user, TransactionScopeService $scope)
    {
        return Folder::scopedQuery($user->folderOrgScopeDepartmentIds())
            ->where('is_active', true)
            ->with('department')
            ->withCount(['transactions as visible_transactions_count' => function ($query) use ($scope, $user) {
                $scope->applyScopeToQuery($query, $user);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function searchTerm(Request $request): string
    {
        return trim($request->string('search')->toString());
    }

    private function folderMatches(Folder $folder, string $term): bool
    {
        if ($term === '') {
            return true;
        }

        $haystack = mb_strtolower(implode(' ', array_filter([
            $folder->name,
            $folder->description,
            $folder->cabinet_number,
            $folder->row_number,
            $folder->box_number,
            $folder->department?->name,
        ])));

        return str_contains($haystack, mb_strtolower($term));
    }
}
