<template>
  <div>
    <h1 class="mb-8 font-bold text-3xl">Users</h1>
    <div class="mb-6 flex justify-between items-center">
      <inertia-link class="btn-indigo" :href="route('users.create')">
        <span>Create</span>
        <span class="hidden md:inline">User</span>
      </inertia-link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <vue-good-table
        mode="remote"
        @on-page-change="onPageChange"
        @on-column-filter="onColumnFilter"
        @on-per-page-change="onPerPageChange"
        :pagination-options="options"
        :columns="columns"
        :totalRows="users.meta.pagination.total"
        :rows="users.data"
        theme="polar-bear"
      >
        <template slot="table-row" slot-scope="props">
          <!-- Code Column -->
          <div v-if="props.column.field === 'code'">
            <Tag
              v-clipboard:copy="props.row.code"
              :value="props.row.code"
              icon="pi pi-copy"
              class="w-full p-mr-2 cursor-pointer"
            />
          </div>

          <!-- Status Column -->
          <div v-else-if="props.column.field === 'status'">
            <span
              :class="[props.row.status ? 'badge-success' : 'badge-danger', 'badge']"
            >{{ props.row.status ? __('Active') : __('In-active') }}</span>
          </div>

          <!-- Action Column -->
          <div v-else-if="props.column.field === 'actions'">
            <inertia-link
              class="p-button-rounded p-button-danger p-button-text"
              :href="route('users.edit', props.row.id)"
            >Edit</inertia-link>

            <Button
              icon="pi pi-trash"
              class="p-button-rounded p-button-danger p-button-text"
              @click="deleteUser(props.row.id)"
            >Delete</Button>
          </div>

          <!-- Remaining Columns -->
          <span v-else>{{props.formattedRow[props.column.field]}}</span>
        </template>
        <div slot="emptystate">
          <no-data-table>
            <template slot="action">
              <inertia-link class="btn-indigo" :href="route('users.create')">
                <span>Create</span>
                <span class="hidden md:inline">User</span>
              </inertia-link>
            </template>
          </no-data-table>
        </div>
      </vue-good-table>
    </div>
  </div>
</template>

<script>
import Icon from '@/Shared/Icon'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import SearchFilter from '@/Shared/SearchFilter'
import NoDataTable from '@/Components/NoDataTable'

export default {
  metaInfo: { title: 'Users' },
  components: {
    Icon,
    NoDataTable,
  },

  layout: Layout,
  props: {
    users: Object,
    roles: Array,
  },
  data() {
    return {
      createForm: false,
      editForm: false,
      currentId: null,
      columns: [
        {
          label: 'Name',
          field: 'fullName',
          filterOptions: {
            enabled: true,
            placeholder: this.__('Search') + ' ' + this.__('Name'),
            filterValue: null,
            trigger: 'enter',
          },
          sortable: false,
        },
        // {
        //   label: this.__("User Name"),
        //   field: "userName",
        //   filterOptions: {
        //     enabled: true,
        //     placeholder: this.__("Search") + " " + this.__("User Name"),
        //     filterValue: null,
        //     trigger: "enter"
        //   },
        //   sortable: false
        // },
        {
          label: this.__('Email'),
          field: 'email',
          filterOptions: {
            enabled: true,
            placeholder: this.__('Search') + ' ' + this.__('Email'),
            filterValue: null,
            trigger: 'enter',
          },
          sortable: false,
        },

        {
          label: this.__('Role'),
          field: 'role',
          filterOptions: {
            enabled: true,
            placeholder: this.__('Search') + ' ' + this.__('Role'),
            filterValue: null,
            filterDropdownItems: this.roles,
          },
          sortable: false,
        },
        {
          label: this.__('Status'),
          field: 'status',
          sortable: false,
          filterOptions: {
            enabled: true,
            placeholder: this.__('Search') + ' ' + this.__('Status'),
            filterValue: null,
            filterDropdownItems: [{ value: 1, text: this.__('Active') }, { value: 0, text: this.__('In-active') }],
          },
          width: '11rem',
        },
        {
          label: this.__('Actions'),
          field: 'actions',
          sortable: false,
          width: '12rem',
        },
      ],
      options: {
        enabled: true,
        mode: 'pages',
        perPage: this.users.meta.pagination.per_page,
        setCurrentPage: this.users.meta.pagination.current_page,
        perPageDropdown: [10, 20, 50, 100],
        dropdownAllowAll: false,
        firstLabel: this.__('First Page'),
        lastLabel: this.__('Last Page'),
        nextLabel: this.__('Next'),
        prevLabel: this.__('Previous'),
        rowsPerPageLabel: this.__('Rows per page'),
        ofLabel: this.__('of'),
        pageLabel: this.__('page'), // for 'pages' mode
        allLabel: this.__('All'),
      },
      serverParams: {
        columnFilters: {},
        sort: {
          field: '',
          type: '',
        },
        page: 1,
        perPage: 10,
      },
      loading: false,
    }
  },

  methods: {
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps)
    },
    onPageChange(params) {
      this.updateParams({ page: params.currentPage })
      this.loadItems()
    },
    onPerPageChange(params) {
      this.updateParams({ perPage: params.currentPerPage })
      this.loadItems()
    },
    onSortChange(params) {
      this.updateParams({
        sort: [
          {
            type: params.sortType,
            field: this.columns[params.columnIndex].field,
          },
        ],
      })
      this.loadItems()
    },
    onColumnFilter(params) {
      this.updateParams(params)
      this.serverParams.page = 1
      this.loadItems()
    },
    getQueryParams() {
      let data = {
        page: this.serverParams.page,
        perPage: this.serverParams.perPage,
      }

      for (const [key, value] of Object.entries(this.serverParams.columnFilters)) {
        if (value) {
          data[key] = value
        }
      }

      return data
    },
    loadItems() {
      this.$inertia.get(route(route().current()), this.getQueryParams(), {
        replace: false,
        preserveState: true,
        preserveScroll: true,
      })
    },
    deleteUser(id) {
      this.$confirm({
        title: 'Are you sure?',
        message: 'Are you sure you want to logout?',
        button: {
          yes: 'Yes',
          no: 'Cancel',
        },
        callback: confirm => {
          if (confirm) {
            this.$inertia.delete(
              route('users.destroy', { id: id }),
              {},
              {
                onSuccess: () => {
                  this.$toast.add({
                    severity: 'info',
                    summary: this.__('Confirmed'),
                    detail: this.__('Record deleted'),
                    life: 3000,
                  })
                },
              },
            )
          }
        },

        /*
        header: this.__("Confirm Delete"),
        message: this.__("Do you want to delete this record?"),
        icon: "pi pi-info-circle",
        acceptClass: "p-button-danger",
        rejectLabel: this.__("Cancel"),
        acceptLabel: this.__("Delete"),
        accept: () => {
          this.$inertia.delete(
            route("users.destroy", { id: id }),
            {},
            {
              onSuccess: () => {
                this.$toast.add({
                  severity: "info",
                  summary: this.__("Confirmed"),
                  detail: this.__("Record deleted"),
                  life: 3000
                });
              }
            }
          );
        },
        reject: () => {}*/
      })
    },
  },
}
</script>
