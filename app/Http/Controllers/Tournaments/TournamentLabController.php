<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Models\TournamentTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;


class TournamentLabController extends Controller
{
    public function __invoke(
        Request $request
    ): View {

        $this->authorize(
            'viewAny',
            TournamentTemplate::class
        );


        $user =
            $request->user();


        $templates =
            TournamentTemplate::query()
            ->ownedBy(
                $user
            )
            ->where(
                'status',
                '!=',
                'ARCHIVED'
            )
            ->withCount(
                'phases'
            )
            ->orderBy(
                'name'
            )
            ->get();


        $selectedTemplate =
            null;


        $templateId =
            (int)
            $request->input(
                'template'
            );


        if ($templateId > 0) {

            $selectedTemplate =
                TournamentTemplate::query()
                ->ownedBy(
                    $user
                )
                ->with([
                    'phases',
                ])
                ->find(
                    $templateId
                );
        }


        return view(
            'tournaments.lab.index',
            compact(
                'templates',
                'selectedTemplate'
            )
        );
    }
}
