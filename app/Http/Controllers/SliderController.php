<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Slider;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
     public function index()
    {
        $sliders = Slider::all();
        return view('sliders.index', compact('sliders'));
    }

    public function create()
    {
        return view('sliders.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'slider1.*' => 'nullable|image|max:2048',
            'slider2.*' => 'nullable|image|max:2048',
        ]);

        // Handle slider1 images
        if ($request->hasFile('slider1')) {
            foreach ($request->file('slider1') as $image) {
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('sliders', $filename, 'public');

                Slider::create([
                    'type' => 'slider1',
                    'photo' => $path,
                ]);
            }
        }

        // Handle slider2 images
        if ($request->hasFile('slider2')) {
            foreach ($request->file('slider2') as $image) {
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('sliders', $filename, 'public');

                Slider::create([
                    'type' => 'slider2',
                    'photo' => $path,
                ]);
            }
        }

        return redirect()->route('sliders.index')->with('success', 'Sliders uploaded successfully.');
    }

    public function edit(Slider $slider)
    {
        return view('sliders.edit', compact('slider'));
    }

    public function update(Request $request, Slider $slider)
    {
        $request->validate([
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if (Storage::disk('public')->exists($slider->photo)) {
                Storage::disk('public')->delete($slider->photo);
            }

            $filename = time() . '_' . uniqid() . '.' . $request->file('photo')->getClientOriginalExtension();
            $slider->photo = $request->file('photo')->storeAs('sliders', $filename, 'public');
        }

        $slider->save();

        return redirect()->route('sliders.index')->with('success', 'Slider updated successfully.');
    }

    public function destroy(Slider $slider)
    {
        if (Storage::disk('public')->exists($slider->photo)) {
            Storage::disk('public')->delete($slider->photo);
        }

        $slider->delete();

        return redirect()->route('sliders.index')->with('success', 'Slider deleted successfully.');
    }
 
}