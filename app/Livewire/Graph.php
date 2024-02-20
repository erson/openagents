<?php

namespace App\Livewire;

use Livewire\Component;

class Graph extends Component
{
    public $nodes = [];
    public $edges = [];

    protected $listeners = ['updateNodePosition'];

    public function mount()
    {
        // Initialize your nodes and edges here
        $this->nodes = [
            ['id' => 1, 'x' => 50, 'y' => 125, 'width' => 220, 'height' => 150, 'title' => 'URL Extractor'],
            ['id' => 2, 'x' => 350, 'y' => 150, 'width' => 220, 'height' => 150, 'title' => 'URL Scraper'],
            ['id' => 3, 'x' => 650, 'y' => 175, 'width' => 220, 'height' => 150, 'title' => 'LLM Inference'],
        ];

        // Assuming each edge connects the centers of the right side of one node to the left side of the next node
        $this->edges = [
            ['from' => 1, 'to' => 2],
            ['from' => 2, 'to' => 3],
        ];
    }

    public function updateNodePosition($nodeId, $x, $y)
    {
        foreach ($this->nodes as $key => $node) {
            if ($node['id'] == $nodeId) {
                // Found the matching node, now update its position
                $this->nodes[$key]['x'] = $x;
                $this->nodes[$key]['y'] = $y;
                break; // Stop the loop once the node is found and updated
            }
        }

        // Optionally, you might want to emit an event to refresh the view or handle additional logic
        // $this->emitSelf('nodeUpdated');
        $this->dispatch('refreshEdge');
    }

    public function render()
    {
        return view('livewire.graph');
    }
}
