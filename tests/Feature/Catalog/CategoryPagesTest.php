<?php

use App\Catalog\Livewire\CategoryForm;
use App\Catalog\Livewire\CategoryIndex;
use App\Catalog\Models\Category;
use App\Catalog\Models\Product;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('muestra el árbol de categorías al comerciante', function () {
    $root = Category::factory()->create(['name' => 'Bebidas']);
    Category::factory()->childOf($root)->create(['name' => 'Gaseosas']);

    $this->actingAs(userWithRole('merchant_admin'))
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSeeInOrder(['Bebidas', 'Gaseosas']);
});

it('bloquea el panel a un cliente', function () {
    $this->actingAs(userWithRole('customer'))
        ->get(route('admin.categories.index'))
        ->assertForbidden();

    Livewire::actingAs(userWithRole('customer'))
        ->test(CategoryIndex::class)
        ->assertForbidden();
});

it('crea una categoría desde el formulario y sugiere el slug', function () {
    Livewire::actingAs(userWithRole('merchant_admin'))
        ->test(CategoryForm::class)
        ->set('name', 'Bebidas frías')
        ->assertSet('slug', 'bebidas-frias')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::query()->where('slug', 'bebidas-frias')->exists())->toBeTrue();
});

it('muestra el error de ciclo en el formulario sin guardar', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->childOf($root)->create();

    Livewire::actingAs(userWithRole('merchant_admin'))
        ->test(CategoryForm::class, ['category' => $root])
        ->set('parentId', $child->id)
        ->call('save')
        ->assertHasErrors('parentId');

    expect($root->fresh()->parent_id)->toBeNull();
});

it('ofrece reasignar cuando solo bloquean productos borrados y luego borra', function () {
    $category = Category::factory()->create();
    $child = Category::factory()->childOf($category)->create();
    $other = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id])->delete();

    $component = Livewire::actingAs(userWithRole('merchant_admin'))
        ->test(CategoryIndex::class)
        ->call('delete', $category->id)
        ->assertSet('reassigningId', $category->id)
        ->assertSee('borrado(s)')
        ->assertSee($other->name);

    $targetIds = collect($component->viewData('targets'))->map(fn (array $row): int => $row['category']->id);
    expect($targetIds->all())->not->toContain($category->id)->not->toContain($child->id)->toContain($other->id);

    $component->set('reassignTarget', 'none')
        ->call('confirmReassign')
        ->assertSet('reassigningId', null);

    expect(Category::query()->whereKey($category->id)->exists())->toBeFalse();
});
