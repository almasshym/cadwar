<template>
    <b-modal
        id="employeesAddModal"
        title="Добавление нового работника"
        @ok="saveItem"
    >
        <b-form @submit="onSubmit">
            <b-form-group
                id="input-group-1"
                label="База данных:"
                label-for="input-1"
            >
                <b-form-select @change="onDbChange" id="input-1">
                    <option v-for="db in dbList" :value="db.id" :key="db.id">{{
                        db.name
                    }}</option>
                </b-form-select>
            </b-form-group>

            <b-form-group id="input-group-2" label="Имя :" label-for="input-2">
                <b-form-input
                    id="input-2"
                    v-model="item.name"
                    type="text"
                    required
                    placeholder="Имя"
                ></b-form-input>
            </b-form-group>
            <b-form-group
                id="input-group-3"
                label="Фамилия :"
                label-for="input-3"
            >
                <b-form-input
                    id="input-3"
                    v-model="item.surname"
                    type="text"
                    required
                    placeholder="Фамилия"
                ></b-form-input>
            </b-form-group>
            <b-form-group
                id="input-group-4"
                label="Отчество :"
                label-for="input-4"
            >
                <b-form-input
                    id="input-4"
                    v-model="item.lastname"
                    type="text"
                    required
                    placeholder="Отчество"
                ></b-form-input>
            </b-form-group>
            <b-form-group id="input-group-5" label="ИИН :" label-for="input-5">
                <b-form-input
                    id="input-5"
                    v-model="item.iin"
                    type="text"
                    required
                    placeholder="ИИН"
                ></b-form-input>
            </b-form-group>
            <hr />
            <b-form-group
                id="input-group-6"
                label="Прикрепленные файлы :"
                label-for="input-6"
            >
                <input
                    @change="onFileChange"
                    type="file"
                    ref="file"
                    style="display: none"
                    multiple
                />
                <b-button @click="$refs.file.click()">
                    <font-awesome-icon icon="plus" /> Добавить файлы
                </b-button>
                <b-list-group>
                    <b-list-group-item
                        v-for="f in files"
                        :key="f.lastModified"
                        class="d-flex justify-content-between align-items-center"
                    >
                        {{ f.name }}
                        <b-link
                            style="color:red;"
                            @click="removeSelectedFile(f.name)"
                        >
                            <font-awesome-icon icon="times" />
                        </b-link>
                    </b-list-group-item>
                </b-list-group>
            </b-form-group>

            <template v-slot:modal-footer>
                <b-button type="button">Отмена</b-button>
                <b-button type="submit" variant="primary">Сохранить</b-button>
            </template>
        </b-form>
    </b-modal>
</template>

<script>
import crudService from "../../../services/crud";
import empService from "../../../services/employee";
export default {
    props: ["dbList"],
    data() {
        return {
            item: {},
            files: []
        };
    },
    methods: {
        onSubmit() {},
        saveItem(e) {
            e.preventDefault();
            crudService
                .postNewItem("employees", this.item, this.files)
                .then(res => {
                    if (res.status === 200) {
                        this.uploadFiles(res.data.id).then(uploaded => {
                            if (uploaded) {
                                this.$emit("created", res.data);
                                this.$bvModal.hide("employeesAddModal");
                            } else window.alert("Ошибка");
                        });
                    } else {
                        window.alert("Ошибка");
                    }
                });
        },
        onDbChange(dbItemId) {
            this.item.base_id = dbItemId;
        },
        onFileChange(e) {
            if (!e.target.value) return;
            window.files = e.target.files;
            for (let i = 0; i < files.length; i++) {
                let f = files[i];
                if (
                    !this.files.find(
                        ef =>
                            ef.name === f.name &&
                            ef.lastModified === f.lastModified &&
                            ef.size === f.size
                    )
                )
                    this.files.push(f);
            }
        },
        removeSelectedFile(name) {
            const idx = this.files.indexOf(
                this.files.find(f => f.name === name)
            );
            this.files.splice(idx, 1);
        },
        uploadFiles(userId) {
            return empService.uploadFile(userId, this.files);
        }
    }
};
</script>
